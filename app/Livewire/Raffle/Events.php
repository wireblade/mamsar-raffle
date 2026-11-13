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
            'Raffle event created successfully with 9 sequences and 55 prize slots.'
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

        if ($attendeeCount < 474) {
            $this->addError(
                'eventStatus',
                "Registration cannot be closed. At least 474 attendees are required. Currently registered: {$attendeeCount}."
            );

            return;
        }

        
        // Calculate flexible winner allocation.
        $allocation = $allocationService->calculate(
            $attendeeCount
        );

        // Save allocation for sequences 1 and 2.
        foreach ($allocation as $sequenceNumber => $data) {

            $sequence = $event->sequences()
                ->where('sequence_number', $sequenceNumber)
                ->firstOrFail();

            $sequence->update([
                'allocated_winners' => $data['total_winners'],
            ]);

            // Save flexible winners per prize item.
            foreach ($data['per_item'] as $index => $winners) {

                $prize = $sequence->prizes()
                    ->where('item_number', $index + 1)
                    ->firstOrFail();

                $prize->update([
                    'allocated_winners' => $winners,
                ]);
            }
        }

        // Save fixed allocations for sequences 3–9.
        $fixedSequences = $event->sequences()
            ->whereBetween('sequence_number', [3, 9])
            ->get();

        foreach ($fixedSequences as $sequence) {

            $sequence->update([
                'allocated_winners' => $sequence->target_winners,
            ]);

            foreach ($sequence->prizes as $prize) {
                $prize->update([
                    'allocated_winners' => $prize->target_winners,
                ]);
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
