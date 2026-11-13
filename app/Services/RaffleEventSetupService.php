<?php

namespace App\Services;

use App\Models\RaffleEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RaffleEventSetupService
{
    public function setup(RaffleEvent $event): void
    {
        DB::transaction(function () use ($event) {
            $event = RaffleEvent::query()
                ->lockForUpdate()
                ->findOrFail($event->id);

            if ($event->status !== 'draft') {
                throw ValidationException::withMessages([
                    'event' => 'Only draft events can be configured.',
                ]);
            }

            if ($event->sequences()->exists()) {
                throw ValidationException::withMessages([
                    'event' => 'This event already has raffle sequences.',
                ]);
            }

            $configurations = config('raffle.sequences', []);

            if (count($configurations) !== 9) {
                throw new \RuntimeException(
                    'The raffle must have exactly 9 sequences.'
                );
            }

            foreach ($configurations as $number => $configuration) {
                $quantities = $configuration['prize_quantities'];

                if (
                    count($quantities) !== $configuration['item_limit']
                    || array_sum($quantities) !== $configuration['target_winners']
                ) {
                    throw new \RuntimeException(
                        "Invalid configuration for sequence {$number}."
                    );
                }

                $sequence = $event->sequences()->create([
                    'sequence_number' => $number,
                    'item_limit' => $configuration['item_limit'],
                    'allocation_type' => $configuration['allocation_type'],
                    'target_winners' => $configuration['target_winners'],
                    'allocated_winners' => 0,
                    'status' => 'pending',
                ]);

                foreach ($quantities as $index => $quantity) {
                    $sequence->prizes()->create([
                        'item_number' => $index + 1,
                        'name' => null,
                        'target_winners' => $quantity,
                        'allocated_winners' => 0,
                        'status' => 'pending',
                    ]);
                }
            }
        });
    }
}
