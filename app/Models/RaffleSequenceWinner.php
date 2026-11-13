<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaffleSequenceWinner extends Model
{
    protected $fillable = [
        'raffle_event_id',
        'raffle_sequence_id',
        'raffle_attendee_id',
        'raffle_prize_id',
        'selected_at',
        'prize_assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'selected_at' => 'datetime',
            'prize_assigned_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(
            RaffleEvent::class,
            'raffle_event_id'
        );
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(
            RaffleSequence::class,
            'raffle_sequence_id'
        );
    }

    public function attendee(): BelongsTo
    {
        return $this->belongsTo(
            RaffleAttendee::class,
            'raffle_attendee_id'
        );
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(
            RafflePrize::class,
            'raffle_prize_id'
        );
    }
}
