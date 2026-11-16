<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Enumerable;
use App\Models\RaffleSequenceWinner;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RaffleResultsExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting
{
    public function __construct(
        protected int $eventId
    ) {}

    public function collection(): Enumerable
    {
        return RaffleSequenceWinner::query()
            ->with([
                'attendee',
                'sequence',
                'prize',
            ])
            ->where('raffle_event_id', $this->eventId)
            ->whereNotNull('raffle_prize_id')
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->sequence?->sequence_number ?? 0)
                    <=> ($b->sequence?->sequence_number ?? 0),
                fn ($a, $b) => ($a->prize?->item_number ?? 0)
                    <=> ($b->prize?->item_number ?? 0),
            ]);
    }

    public function headings(): array
    {
        return [
            'Sequence',
            'Prize Item',
            'Winning Raffle Number',
        ];
    }

    public function map($winner): array
    {
        return [
            $winner->sequence?->sequence_number,
            $winner->prize?->name,
            $winner->attendee?->raffle_number,
        ];
    }

    public function columnFormats(): array
{
    return [
        'C' => NumberFormat::FORMAT_TEXT,
    ];
}
}
