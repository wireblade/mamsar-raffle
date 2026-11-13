<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('raffle_sequence_winners', function (Blueprint $table) {
        $table->id();

        $table->foreignId('raffle_event_id')
            ->constrained('raffle_events')
            ->cascadeOnDelete();

        $table->foreignId('raffle_sequence_id')
            ->constrained('raffle_sequences')
            ->cascadeOnDelete();

        $table->foreignId('raffle_attendee_id')
            ->constrained('raffle_attendees')
            ->cascadeOnDelete();

        $table->foreignId('raffle_prize_id')
            ->nullable()
            ->constrained('raffle_prizes')
            ->restrictOnDelete();

        $table->timestamp('selected_at')->useCurrent();

        $table->timestamp('prize_assigned_at')->nullable();

        $table->timestamps();

        $table->unique(
            ['raffle_event_id', 'raffle_attendee_id'],
            'raffle_winners_event_attendee_unique'
        );
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raffle_sequence_winners');
    }
};
