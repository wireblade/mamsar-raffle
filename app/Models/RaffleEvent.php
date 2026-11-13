<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaffleEvent extends Model
{
    protected $fillable = [
        'name',
        'event_date',
        'status',
        'total_attendees',
        'registration_closed_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'registration_closed_at' => 'datetime',
            'total_attendees' => 'integer',
        ];
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(RaffleAttendee::class);
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(RaffleSequence::class);
    }

    public function winners(): HasMany
    {
        return $this->hasMany(RaffleSequenceWinner::class);
    }
}
