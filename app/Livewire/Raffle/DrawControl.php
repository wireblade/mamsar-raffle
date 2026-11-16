<?php

namespace App\Livewire\Raffle;

use Livewire\Attributes\Url;
use App\Models\RafflePrize;
use App\Services\RaffleDrawService;
use Illuminate\Validation\ValidationException;
use App\Models\RaffleEvent;
use Livewire\Component;

class DrawControl extends Component
{
    public RaffleEvent $event;

    #[Url(as: 'sequence')]
public int $selectedSequence = 1;

    public function mount(RaffleEvent $event): void
    {
        $this->event = $event;

        if (! in_array($event->status, ['ready', 'ongoing', 'completed'], true)) {
            abort(403, 'This raffle event is not ready for drawing.');
        }
    }

    public function selectSequence(int $sequenceNumber): void
    {
        if ($sequenceNumber < 1 || $sequenceNumber > 9) {
            return;
        }

        $this->selectedSequence = $sequenceNumber;
    }

    public function startDraw(
    int $prizeId,
    RaffleDrawService $drawService
): void {
    $prize = RafflePrize::findOrFail($prizeId);

    try {
        $count = $drawService->drawPrize(
    $this->event,
    $prize
);

$this->event->refresh();


if ($count === 0) {
    session()->flash(
        'draw_success',
        'This prize has no allocated winners and was automatically marked as completed.'
    );

    return;
}


$winningNumbers = $prize->fresh()
    ->winners()
    ->with('attendee')
    ->get()
    ->pluck('attendee.raffle_number')
    ->values()
    ->all();

$this->dispatch(
    'raffle-prize-drawn',
    prizeId: $prize->id,
    numbers: $winningNumbers
);

        session()->flash(
            'draw_success',
            "Successfully drawn {$count} winners!"
        );

    } catch (ValidationException $e) {
        $this->addError(
            'draw',
            $e->validator->errors()->first()
        );
    }
}

    public function render()
    {
        $sequences = $this->event->sequences()
            ->with(['prizes' => function ($query) {
    $query->orderBy('item_number')
        ->with('winners.attendee');
}])
            ->orderBy('sequence_number')
            ->get();

        return view('livewire.raffle.draw-control', [
            'sequences' => $sequences,
            'currentSequence' => $sequences->firstWhere(
                'sequence_number',
                $this->selectedSequence
            ),
        ])->layout('layouts.app.guest');
    }
}
