<?php

namespace App\Services;

use InvalidArgumentException;

class RaffleAllocationService
{
    public function calculate(
        int $totalAttendees,
        array $sequences
    ): array {
        $reservedWinners = 0;

        // Calculate exact reserved winners for Sequences 3–9.
        foreach (range(3, 9) as $number) {
            $items = $sequences[$number] ?? [];

            foreach ($items as $quantity) {
                if (! is_int($quantity) || $quantity < 0) {
                    throw new InvalidArgumentException(
                        'Prize winner quantities must be non-negative integers.'
                    );
                }

                $reservedWinners += $quantity;
            }
        }

        if ($reservedWinners > $totalAttendees) {
            throw new InvalidArgumentException(
                'Reserved winners exceed registered attendees.'
            );
        }

        $remainingWinners = $totalAttendees - $reservedWinners;

        // Use configured prize targets as allocation weights.
        $sequence1Items = $sequences[1] ?? [];
        $sequence2Items = $sequences[2] ?? [];

        if (empty($sequence1Items) || empty($sequence2Items)) {
            throw new InvalidArgumentException(
                'Sequences 1 and 2 must have prize items.'
            );
        }

        $allDynamicItems = array_merge(
            $sequence1Items,
            $sequence2Items
        );

        foreach ($allDynamicItems as $quantity) {
            if (! is_int($quantity) || $quantity < 1) {
                throw new InvalidArgumentException(
                    'Sequences 1 and 2 require positive prize targets.'
                );
            }
        }

        $totalWeight = array_sum($allDynamicItems);

        // Distribute remaining winners proportionally per item.
        $allocatedItems = $this->distributeProportionally(
            $remainingWinners,
            $allDynamicItems
        );

        $sequence1Count = count($sequence1Items);

        $sequence1Allocation = array_slice(
            $allocatedItems,
            0,
            $sequence1Count
        );

        $sequence2Allocation = array_slice(
            $allocatedItems,
            $sequence1Count
        );

        return [
            1 => [
                'total_winners' => array_sum($sequence1Allocation),
                'per_item' => $sequence1Allocation,
            ],
            2 => [
                'total_winners' => array_sum($sequence2Allocation),
                'per_item' => $sequence2Allocation,
            ],
        ];
    }

    private function distributeProportionally(
        int $winners,
        array $weights
    ): array {
        $totalWeight = array_sum($weights);

        $allocation = [];
        $remainders = [];

        $assigned = 0;

        foreach ($weights as $index => $weight) {
            $exact = ($winners * $weight) / $totalWeight;

            $base = (int) floor($exact);

            $allocation[$index] = $base;
            $remainders[$index] = $exact - $base;

            $assigned += $base;
        }

        // Distribute leftover winners to largest remainders.
        $leftover = $winners - $assigned;

        arsort($remainders);

        foreach (array_keys($remainders) as $index) {
            if ($leftover <= 0) {
                break;
            }

            $allocation[$index]++;
            $leftover--;
        }

        ksort($allocation);

        return array_values($allocation);
    }
}
