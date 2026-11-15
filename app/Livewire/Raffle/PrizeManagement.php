<?php

namespace App\Livewire\Raffle;

use App\Models\RaffleEvent;
use App\Models\RafflePrize;
use Livewire\Component;

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
