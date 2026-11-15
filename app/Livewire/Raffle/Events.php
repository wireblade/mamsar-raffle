<?php

namespace App\Livewire\Raffle;

use App\Models\RaffleEvent;
use App\Services\RaffleEventSetupService;
use App\Services\RaffleAllocationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Events extends Component
{
    public string $name = '';

    public ?string $eventDate = null;

    public bool $showCreateModal = false;

    public function openCreateModal(): void
    {
        $this->resetValidation();

        $this->reset([
            'name',
            'eventDate',
        ]);

        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;

        $this->resetValidation();
    }

    public function createEvent(
        RaffleEventSetupService $setupService
    ): void {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'eventDate' => ['nullable', 'date'],
        ]);

        try {
            DB::transaction(function () use (
                $validated,
                $setupService
            ) {
                $event = RaffleEvent::create([
                    'name' => $validated['name'],
                    'event_date' => $validated['eventDate'] ?: null,
                    'status' => 'draft',
                ]);

                $setupService->setup($event);
            });
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'name' => 'Unable to create the raffle event. Please try again.',
            ]);
        }

        $this->closeCreateModal();

        session()->flash(
            'success',
            'Raffle event created successfully with 9 sequences. You can now configure the prize items.'
        );
    }


public function openRegistration(int $eventId): void
{
    
    $event = RaffleEvent::findOrFail($eventId);

    if ($event->status !== 'draft') {
        $this->addError(
            'eventStatus',
            'Only draft events can open registration.'
        );

        return;
    }

    $event->update([
        'status' => 'registration',
    ]);

    session()->flash(
        'success',
        "Registration is now open for {$event->name}."
    );
}



public function closeRegistration(
    int $eventId,
    RaffleAllocationService $allocationService
): void {
    DB::transaction(function () use (
        $eventId,
        $allocationService
    ) {
        $event = RaffleEvent::query()
            ->lockForUpdate()
            ->findOrFail($eventId);

        if ($event->status !== 'registration') {
            $this->addError(
                'eventStatus',
                'Only events with open registration can be closed.'
            );

            return;
        }

        
$attendeeCount = $event->attendees()->count();

// Get sequences 3–9 and their configured prize items.
$fixedSequences = $event->sequences()
    ->whereBetween('sequence_number', [3, 9])
    ->with('prizes')
    ->get();

// Calculate the total reserved winners dynamically.
$reservedWinners = $fixedSequences->sum(
    fn ($sequence) => $sequence->prizes->sum('target_winners')
);

// Ensure there are enough attendees for reserved prizes.
if ($attendeeCount < $reservedWinners) {
    $this->addError(
        'eventStatus',
        "Registration cannot be closed. {$reservedWinners} winners are reserved for Sequences 3–9, but only {$attendeeCount} attendees are registered."
    );

    return;
}


        
        
 // Get all sequences and their prize items.
$sequences = $event->sequences()
    ->with(['prizes' => function ($query) {
        $query->orderBy('item_number');
    }])
    ->orderBy('sequence_number')
    ->get();


// Validate all nine sequences before allocation.
if ($sequences->count() !== 9) {
    $this->addError(
        'eventStatus',
        'The raffle must have exactly 9 sequences.'
    );

    return;
}

foreach (range(1, 9) as $number) {
    $sequence = $sequences->firstWhere('sequence_number', $number);

    if (! $sequence) {
        $this->addError(
            'eventStatus',
            "Sequence {$number} is missing."
        );

        return;
    }

    if ($sequence->prizes->isEmpty()) {
        $this->addError(
            'eventStatus',
            "Sequence {$number} must have at least one prize item."
        );

        return;
    }

    foreach ($sequence->prizes as $prize) {
        if (blank($prize->name)) {
            $this->addError(
                'eventStatus',
                "Sequence {$number}, Item {$prize->item_number} must have a prize name."
            );

            return;
        }

        if (
            filter_var(
                $prize->target_winners,
                FILTER_VALIDATE_INT
            ) === false ||
            (int) $prize->target_winners < 1
        ) {
            $this->addError(
                'eventStatus',
                "Sequence {$number}, Item {$prize->item_number} must have at least 1 target winner."
            );

            return;
        }
    }
}


// Prepare prize winner quantities grouped by sequence.
$prizeTargets = [];

foreach ($sequences as $sequence) {
    $prizeTargets[$sequence->sequence_number] = $sequence->prizes
        ->pluck('target_winners')
        ->map(fn ($quantity) => (int) $quantity)
        ->all();
}

// Calculate the dynamic allocation.
try {
    $allocation = $allocationService->calculate(
        $attendeeCount,
        $prizeTargets
    );
} catch (\InvalidArgumentException $e) {
    $this->addError('eventStatus', $e->getMessage());

    return;
}


        
// Save dynamic allocations for Sequences 1 and 2.
foreach ($allocation as $sequenceNumber => $data) {

    // Use the sequences and prizes already loaded above.
    $sequence = $sequences->firstWhere(
        'sequence_number',
        $sequenceNumber
    );

    if (! $sequence) {
        throw new \RuntimeException(
            "Sequence {$sequenceNumber} was not found."
        );
    }

    // Update sequence totals.
    $sequence->update([
        'target_winners' => $sequence->prizes->sum('target_winners'),
        'allocated_winners' => $data['total_winners'],
    ]);

    // Save allocated winners using actual prize records.
    $prizes = $sequence->prizes->values();

    foreach ($data['per_item'] as $index => $winners) {

        $prize = $prizes->get($index);

        if (! $prize) {
            throw new \RuntimeException(
                "Prize item {$index} was not found in Sequence {$sequenceNumber}."
            );
        }

        $prize->update([
            'allocated_winners' => $winners,
        ]);
    }
}


        
// Save exact reserved allocations for Sequences 3–9.
foreach ($fixedSequences as $sequence) {

    // Calculate total winners from the actual prize items.
    $sequenceReservedWinners = $sequence->prizes
        ->sum('target_winners');

    // Update the sequence's allocated winners.
    $sequence->update([
        'target_winners' => $sequenceReservedWinners,
        'allocated_winners' => $sequenceReservedWinners,
    ]);

    // Reserve the exact quantity assigned to each prize item.
    foreach ($sequence->prizes as $prize) {
        $prize->update([
            'allocated_winners' => $prize->target_winners,
        ]);
    }
}


// Verify all sequence allocations before closing registration.
$totalAllocatedWinners = (int) $event->sequences()
    ->sum('allocated_winners');

if ($totalAllocatedWinners !== $attendeeCount) {
    throw new \RuntimeException(
        "Allocation mismatch: {$totalAllocatedWinners} winners allocated "
        . "for {$attendeeCount} registered attendees."
    );
}

// Verify every sequence total matches its prize allocations.
foreach ($event->sequences()->with('prizes')->get() as $sequence) {
    $prizeAllocatedWinners = $sequence->prizes
        ->sum('allocated_winners');

    if ((int) $sequence->allocated_winners !== (int) $prizeAllocatedWinners) {
        throw new \RuntimeException(
            "Allocation mismatch in Sequence {$sequence->sequence_number}."
        );
    }
}


        $event->update([
            'status' => 'ready',
            'total_attendees' => $attendeeCount,
            'registration_closed_at' => now(),
        ]);
    });

    if ($this->getErrorBag()->has('eventStatus')) {
        return;
    }

    session()->flash(
        'success',
        'Registration closed successfully. The event is ready for the raffle.'
    );
}



    public function render()
    {
        return view('livewire.raffle.events', [
            'events' => RaffleEvent::query()
                ->withCount(['attendees', 'sequences'])
                ->latest()
                ->get(),
        ])->layout('layouts.app.guest');
    }
}
