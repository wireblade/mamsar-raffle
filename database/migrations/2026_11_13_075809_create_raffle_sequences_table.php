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
        Schema::create('raffle_sequences', function (Blueprint $table) {
        $table->id();

        $table->foreignId('raffle_event_id')
            ->constrained('raffle_events')
            ->cascadeOnDelete();

        $table->unsignedTinyInteger('sequence_number');

        $table->unsignedTinyInteger('item_limit');

        $table->enum('allocation_type', [
            'dynamic',
            'fixed',
        ]);

        $table->unsignedInteger('target_winners');

        $table->unsignedInteger('allocated_winners')->default(0);

        $table->enum('status', [
            'pending',
            'drawing',
            'allocating',
            'completed',
        ])->default('pending');

        $table->timestamps();

        $table->unique(
            ['raffle_event_id', 'sequence_number'],
            'raffle_sequences_event_number_unique'
        );
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raffle_sequences');
    }
};
