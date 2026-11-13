<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RaffleAttendee extends Model
{
    protected $fillable = [
        'raffle_event_id',
        'raffle_number',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(RaffleEvent::class, 'raffle_event_id');
    }

    public function sequenceWinner(): HasOne
    {
        return $this->hasOne(
            RaffleSequenceWinner::class,
            'raffle_attendee_id'
        );
    }
}
