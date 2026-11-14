<?php

namespace App\Services;

use App\Models\RaffleAttendee;
use App\Models\RaffleEvent;
use App\Models\RafflePrize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RaffleDrawService
{
    public function selectRandomAttendees(
        RaffleEvent $event,
        int $quantity
    ): Collection {
        if (! in_array($event->status, ['ready', 'ongoing'], true)) {
            throw ValidationException::withMessages([
                'draw' => 'This raffle event is not ready for drawing.',
            ]);
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'draw' => 'The number of winners must be greater than zero.',
            ]);
        }

        $eligibleAttendees = RaffleAttendee::query()
            ->where('raffle_event_id', $event->id)
            ->whereDoesntHave('sequenceWinner')
            ->inRandomOrder()
            ->limit($quantity)
            ->get();

        if ($eligibleAttendees->count() !== $quantity) {
            throw ValidationException::withMessages([
                'draw' => 'Not enough eligible attendees remain.',
            ]);
        }

        return $eligibleAttendees;
    }

public function drawPrize(
    RaffleEvent $event,
    RafflePrize $prize
): int {
    return DB::transaction(function () use ($event, $prize) {

        // Lock the event to serialize draw requests.
        $lockedEvent = RaffleEvent::query()
            ->lockForUpdate()
            ->findOrFail($event->id);

        if (! in_array($lockedEvent->status, ['ready', 'ongoing'], true)) {
            throw ValidationException::withMessages([
                'draw' => 'This event is not ready for drawing.',
            ]);
        }

        // Load the prize and its sequence.
        $lockedPrize = RafflePrize::query()
            ->with('sequence')
            ->findOrFail($prize->id);

        $sequence = $lockedPrize->sequence;

        if ($sequence->raffle_event_id !== $lockedEvent->id) {
            throw ValidationException::withMessages([
                'draw' => 'This prize does not belong to the selected event.',
            ]);
        }

        // Only allow drawing the earliest unfinished prize.
        $nextPrize = RafflePrize::query()
            ->whereHas('sequence', function ($query) use ($lockedEvent) {
                $query->where('raffle_event_id', $lockedEvent->id);
            })
            ->where('raffle_prizes.status', 'pending')
            ->join(
                'raffle_sequences',
                'raffle_prizes.raffle_sequence_id',
                '=',
                'raffle_sequences.id'
            )
            ->orderBy('raffle_sequences.sequence_number')
            ->orderBy('raffle_prizes.item_number')
            ->select('raffle_prizes.id')
            ->first();

        if (! $nextPrize || $nextPrize->id !== $lockedPrize->id) {
            throw ValidationException::withMessages([
                'draw' => 'Please complete earlier prize items first.',
            ]);
        }

        $quantity = $lockedPrize->allocated_winners;

        // Select eligible attendees randomly.
        $winners = $this->selectRandomAttendees(
            $lockedEvent,
            $quantity
        );

        foreach ($winners as $attendee) {
            $lockedPrize->winners()->create([
                'raffle_event_id' => $lockedEvent->id,
                'raffle_sequence_id' => $sequence->id,
                'raffle_attendee_id' => $attendee->id,
                'selected_at' => now(),
                'prize_assigned_at' => now(),
            ]);
        }

        $lockedPrize->update([
            'status' => 'completed',
        ]);

        $lockedEvent->update([
    'status' => 'ongoing',
]);

        return $winners->count();
    });
}

}
