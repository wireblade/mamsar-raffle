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

            // Create exactly nine sequences without fixed prize items.
            for ($number = 1; $number <= 9; $number++) {

                $event->sequences()->create([
                    'sequence_number' => $number,
                    'item_limit' => 0,
                    'allocation_type' => in_array($number, [1, 2], true)
                        ? 'dynamic'
                        : 'fixed',
                    'target_winners' => 0,
                    'allocated_winners' => 0,
                    'status' => 'pending',
                ]);
            }
        });
    }
}
