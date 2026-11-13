<?php

namespace App\Services;

use InvalidArgumentException;

class RaffleAllocationService
{
    public function calculate(int $totalAttendees): array
    {
        $fixedWinners = 474;

        if ($totalAttendees < $fixedWinners) {
            throw new InvalidArgumentException(
                'Not enough attendees to fill the fixed winner slots.'
            );
        }

        $dynamicWinners = $totalAttendees - $fixedWinners;

        // Original allocation: 156 winners for Sequence 1,
        // 500 winners for Sequence 2.
        $sequence1 = (int) round(
            $dynamicWinners * (156 / 656)
        );

        $sequence2 = $dynamicWinners - $sequence1;

        return [
            1 => [
                'total_winners' => $sequence1,
                'per_item' => $this->distribute($sequence1, 6),
            ],
            2 => [
                'total_winners' => $sequence2,
                'per_item' => $this->distribute($sequence2, 10),
            ],
        ];
    }

    private function distribute(int $winners, int $items): array
    {
        $base = intdiv($winners, $items);
        $remainder = $winners % $items;

        $distribution = [];

        for ($i = 0; $i < $items; $i++) {
            $distribution[] = $base + ($i < $remainder ? 1 : 0);
        }

        return $distribution;
    }
}
