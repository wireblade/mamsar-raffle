<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaffleSequence extends Model
{
    
protected $fillable = [
    'raffle_event_id',
    'sequence_number',
    'item_limit',
    'allocation_type',
    'allocation_weight',
    'target_winners',
    'allocated_winners',
    'status',
];


    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'item_limit' => 'integer',
            'target_winners' => 'integer',
            'allocated_winners' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(
            RaffleEvent::class,
            'raffle_event_id'
        );
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(
            RafflePrize::class,
            'raffle_sequence_id'
        );
    }

    public function winners(): HasMany
    {
        return $this->hasMany(
            RaffleSequenceWinner::class,
            'raffle_sequence_id'
        );
    }
}
