<?php

namespace App\Livewire\Raffle;

use App\Models\RaffleEvent;
use App\Models\RafflePrize;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class PrizeManagement extends Component
{
    public RaffleEvent $event;

    public array $prizes = [];

    public function mount(RaffleEvent $event): void
    {
        $this->event = $event;

        $this->loadPrizes();
    }

    

public function loadPrizes(): void
{
    $sequences = $this->event->sequences()
        ->with(['prizes' => function ($query) {
            $query->orderBy('item_number');
        }])
        ->orderBy('sequence_number')
        ->get();

    $this->prizes = [];

    foreach ($sequences as $sequence) {
        foreach ($sequence->prizes as $prize) {
            $this->prizes[$prize->id] = [
                'name' => $prize->name ?? '',
                'target_winners' => $prize->target_winners,
                'allocated_winners' => $prize->allocated_winners,
                'status' => $prize->status,
            ];
        }
    }
}


public function addPrize(int $sequenceId): void
{
    $event = $this->event->fresh();

    // Prize items can only be added before registration closes.
    if (! in_array($event->status, ['draft', 'registration'], true)) {
        $this->addError(
            'prizeManagement',
            'Prize items cannot be added after registration closes.'
        );

        return;
    }

    DB::transaction(function () use ($sequenceId) {

        $sequence = $this->event->sequences()
            ->lockForUpdate()
            ->findOrFail($sequenceId);

        
// Find the lowest available item number (1–255).
$usedNumbers = $sequence->prizes()
    ->pluck('item_number')
    ->map(fn ($number) => (int) $number)
    ->all();

$nextItemNumber = null;

for ($number = 1; $number <= 255; $number++) {
    if (! in_array($number, $usedNumbers, true)) {
        $nextItemNumber = $number;
        break;
    }
}

if ($nextItemNumber === null) {
    throw \Illuminate\Validation\ValidationException::withMessages([
        'prizeManagement' => 'This sequence has reached the maximum of 255 prize items.',
    ]);
}


        // Create a new pending prize item.
        $sequence->prizes()->create([
            'item_number' => $nextItemNumber,
            'name' => null,
            'target_winners' => 1,
            'allocated_winners' => 0,
            'status' => 'pending',
        ]);

        // Keep the sequence item count synchronized.
        $sequence->update([
            'item_limit' => $sequence->prizes()->count(),
        ]);
    });

    $this->loadPrizes();

    $this->dispatch(
        'prize-saved',
        message: 'New prize item added successfully!'
    );
}


public function deletePrize(int $prizeId): void
{
    $event = $this->event->fresh();

    // Only allow deletion before registration closes.
    if (! in_array($event->status, ['draft', 'registration'], true)) {
        $this->addError(
            'prizeManagement',
            'Prize items cannot be deleted after registration closes.'
        );

        return;
    }

    DB::transaction(function () use ($prizeId) {

        $prize = RafflePrize::query()
            ->whereHas('sequence', function ($query) {
                $query->where('raffle_event_id', $this->event->id);
            })
            ->lockForUpdate()
            ->findOrFail($prizeId);

        // Never delete a prize that has started drawing.
        if ($prize->status !== 'pending') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'prizeManagement' => 'Only pending prize items can be deleted.',
            ]);
        }

        // Protect prizes that already have winners.
        if ($prize->winners()->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'prizeManagement' => 'This prize already has winners and cannot be deleted.',
            ]);
        }

        $sequence = $prize->sequence;

        $prize->delete();

        // Update the sequence's current item count.
        $sequence->update([
            'item_limit' => $sequence->prizes()->count(),
        ]);
    });

    $this->loadPrizes();

    $this->dispatch(
        'prize-saved',
        message: 'Prize item deleted successfully!'
    );
}


public function savePrize(int $prizeId): void
{
    $prize = RafflePrize::query()
        ->whereHas('sequence', function ($query) {
            $query->where('raffle_event_id', $this->event->id);
        })
        ->findOrFail($prizeId);

    $event = $this->event->fresh();

    // Drawing and completed prizes cannot be edited.
    if ($prize->status !== 'pending') {
        $this->addError(
            "prizes.{$prizeId}.name",
            'This prize can no longer be edited.'
        );

        return;
    }

    // Prize names can be edited while pending.
    $rules = [
        "prizes.{$prizeId}.name" => [
            'required',
            'string',
            'max:255',
        ],
    ];

    // Winner quantities can only change before registration closes.
    $canEditQuantity = in_array(
        $event->status,
        ['draft', 'registration'],
        true
    );

    if ($canEditQuantity) {
        $rules["prizes.{$prizeId}.target_winners"] = [
            'required',
            'integer',
            'min:1',
        ];
    }

    $validated = $this->validate($rules);

    $updates = [
        'name' => trim($validated['prizes'][$prizeId]['name']),
    ];

    if ($canEditQuantity) {
        $updates['target_winners'] =
            $validated['prizes'][$prizeId]['target_winners'];
    }

   $prize->update($updates);

$this->loadPrizes();

$this->dispatch(
    'prize-saved',
    message: "Prize Item {$prize->item_number} updated successfully!"
);
}


    public function render()
    {
        return view('livewire.raffle.prize-management', [
            'sequences' => $this->event->sequences()
                ->with(['prizes' => function ($query) {
                    $query->orderBy('item_number');
                }])
                ->orderBy('sequence_number')
                ->get(),
        ])->layout('layouts.app.guest');
    }
}
