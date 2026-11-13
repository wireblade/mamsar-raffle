<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RafflePrize extends Model
{
    protected $fillable = [
        'raffle_sequence_id',
        'item_number',
        'name',
        'target_winners',
        'allocated_winners',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'item_number' => 'integer',
            'target_winners' => 'integer',
            'allocated_winners' => 'integer',
        ];
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(
            RaffleSequence::class,
            'raffle_sequence_id'
        );
    }

    public function winners(): HasMany
    {
        return $this->hasMany(
            RaffleSequenceWinner::class,
            'raffle_prize_id'
        );
    }
}
