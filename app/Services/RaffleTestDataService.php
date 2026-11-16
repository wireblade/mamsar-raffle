<?php

namespace App\Services;

use App\Models\RaffleAttendee;
use App\Models\RaffleEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RaffleTestDataService
{
    public function generate(int $attendeeCount): RaffleEvent
    {
        if (! app()->environment(['local', 'testing'])) {
            throw ValidationException::withMessages([
                'generator' => 'Test data generation is only allowed in local or testing environments.',
            ]);
        }

        $sequences = config('raffle_test.sequences', []);

        if (count($sequences) !== 9) {
            throw ValidationException::withMessages([
                'generator' => 'The test configuration must contain exactly 9 sequences.',
            ]);
        }

        $fixedWinners = collect($sequences)
            ->filter(fn ($prizes, $number) => (int) $number >= 3)
            ->flatten(1)
            ->sum('winners');

        if ($attendeeCount < $fixedWinners) {
            throw ValidationException::withMessages([
                'attendeeCount' => "At least {$fixedWinners} attendees are required.",
            ]);
        }

        return DB::transaction(function () use ($attendeeCount, $sequences) {

            // Create a separate test event.
            $event = RaffleEvent::create([
    'name' => 'TEST RAFFLE - ' . Str::upper(Str::random(8)),
    'event_date' => now()->toDateString(),
    'status' => 'registration',
    'total_attendees' => $attendeeCount,
]);

            // Create all nine sequences and their prizes.
            foreach ($sequences as $sequenceNumber => $prizes) {

                $sequence = $event->sequences()->create([
                    'sequence_number' => (int) $sequenceNumber,
                    'item_limit' => count($prizes),
                    'allocation_type' => (int) $sequenceNumber <= 2
                        ? 'dynamic'
                        : 'fixed',
                    'target_winners' => collect($prizes)->sum('winners'),
                    'allocated_winners' => 0,
                    'status' => 'pending',
                ]);

                foreach ($prizes as $index => $prize) {
                    $sequence->prizes()->create([
    'name' => $prize['name'],
    'item_number' => $index + 1,
    'target_winners' => $prize['winners'],
    'allocated_winners' => 0,
    'status' => 'pending',
]);
                }
            }

            // Generate unique raffle numbers: 1 to N.
            $now = now();

            foreach (array_chunk(range(1, $attendeeCount), 500) as $numbers) {

                $rows = [];

                foreach ($numbers as $number) {
                    $rows[] = [
                        'raffle_event_id' => $event->id,
                        'raffle_number' => (string) $number,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                RaffleAttendee::insert($rows);
            }

            return $event;
        });
    }

    public function reset(): void
{
    abort_unless(app()->environment(['local', 'testing']), 403);

    DB::transaction(function () {

        // Delete all raffle winners first.
        DB::table('raffle_sequence_winners')->delete();

        // Delete all registered attendees.
        DB::table('raffle_attendees')->delete();

        // Delete all raffle prizes.
        DB::table('raffle_prizes')->delete();

        // Delete all raffle sequences.
        DB::table('raffle_sequences')->delete();

        // Delete all raffle events.
        DB::table('raffle_events')->delete();

    });
}
}
