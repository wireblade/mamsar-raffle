<?php

namespace App\Livewire\Raffle;

use App\Exports\RaffleResultsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\RaffleSequenceWinner;
use App\Models\RaffleEvent;
use Livewire\Component;

class ResultsSummary extends Component
{
    public RaffleEvent $event;

    public function mount(RaffleEvent $event): void
    {
        $this->event = $event;
    }

    public function exportExcel()
{
    return Excel::download(
        new RaffleResultsExport($this->event->id),
        'raffle-results-' . $this->event->id . '.xlsx'
    );
}

    public function render()
    {
        $sequences = $this->event->sequences()
            ->with([
                'prizes' => function ($query) {
                    $query->orderBy('item_number');
                },
            ])
            ->orderBy('sequence_number')
            ->get();

        $winners = RaffleSequenceWinner::query()
    ->with('attendee')
    ->where('raffle_event_id', $this->event->id)
    ->whereNotNull('raffle_prize_id')
    ->orderBy('selected_at')
    ->get()
    ->groupBy('raffle_prize_id');

$totalWinners = RaffleSequenceWinner::where(
    'raffle_event_id',
    $this->event->id
)->count();

        return view('livewire.raffle.results-summary', [
            'sequences' => $sequences,
            'totalWinners' => $totalWinners,
    'winners' => $winners,
        ])->layout('layouts.app.guest');
    }
}
