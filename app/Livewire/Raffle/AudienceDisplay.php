<?php

namespace App\Livewire\Raffle;

use App\Models\RaffleEvent;
use Livewire\Component;

class AudienceDisplay extends Component
{
    public RaffleEvent $event;

    public function mount(RaffleEvent $event): void
    {
        $this->event = $event;

        if (! in_array($event->status, ['ready', 'ongoing', 'completed'], true)) {
            abort(403, 'This raffle event is not ready for display.');
        }
    }

    
public function render()
{
    $currentPrize = $this->event->sequences()
        ->with(['prizes' => function ($query) {
            $query->where('status', 'completed')
                ->with('winners.attendee')
                ->orderByDesc('id');
        }])
        ->get()
        ->flatMap(fn ($sequence) => $sequence->prizes)
        ->sortByDesc('id')
        ->first();

    return view('livewire.raffle.audience-display', [
        'currentPrize' => $currentPrize,
    ])->layout('layouts.app.guest');
}

}