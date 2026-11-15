<?php

namespace App\Livewire\Raffle;

use App\Models\RaffleEvent;
use App\Models\RafflePrize;
use Livewire\Component;

class AudienceDisplay extends Component
{
    public RaffleEvent $event;

    public ?int $lastSeenPrizeId = null;

    public function mount(RaffleEvent $event): void
    {
        $this->event = $event;

        if (! in_array($event->status, ['ready', 'ongoing', 'completed'], true)) {
            abort(403, 'This raffle event is not ready for display.');
        }

        // Already completed prizes should appear immediately, not replay on page load.
        $this->lastSeenPrizeId = $this->latestCompletedPrize()?->id;
    }

    
private function latestCompletedPrize(): ?RafflePrize
{
    return RafflePrize::query()
        ->whereHas('sequence', function ($query) {
            $query->where('raffle_event_id', $this->event->id);
        })
        

->where('raffle_prizes.status', 'completed')
->where('raffle_prizes.allocated_winners', '>', 0)

->whereHas('winners')
->with(['sequence', 'winners.attendee'])

        ->join(
            'raffle_sequences',
            'raffle_prizes.raffle_sequence_id',
            '=',
            'raffle_sequences.id'
        )
        ->orderByDesc('raffle_sequences.sequence_number')
        ->orderByDesc('raffle_prizes.item_number')
        ->select('raffle_prizes.*')
        ->first();
}


    public function checkForNewDraw(): void
    {
        $prize = $this->latestCompletedPrize();

        if (! $prize || $prize->id === $this->lastSeenPrizeId) {
            return;
        }

        logger()->info('AUDIENCE NEW DRAW DETECTED', [
    'prize_id' => $prize->id,
    'previous_prize_id' => $this->lastSeenPrizeId,
]);

        $this->lastSeenPrizeId = $prize->id;

        $this->dispatch(
            'audience-draw-detected',
            prizeId: $prize->id,
            prizeName: $prize->name ?: 'Unnamed Prize',
            sequenceNumber: $prize->sequence->sequence_number,
            itemNumber: $prize->item_number,
            numbers: $prize->winners->pluck('attendee.raffle_number')->values()->all()
        );
    }

    public function render()
    {
        return view('livewire.raffle.audience-display', [
            'currentPrize' => $this->latestCompletedPrize(),
        ])->layout('layouts.app.guest');
    }
}
