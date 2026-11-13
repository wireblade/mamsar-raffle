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
        Schema::create('raffle_prizes', function (Blueprint $table) {
        $table->id();

        $table->foreignId('raffle_sequence_id')
            ->constrained('raffle_sequences')
            ->cascadeOnDelete();

        $table->unsignedTinyInteger('item_number');

        $table->string('name')->nullable();

        $table->unsignedInteger('target_winners')->default(0);

        $table->unsignedInteger('allocated_winners')->default(0);

        $table->enum('status', [
            'pending',
            'drawing',
            'completed',
        ])->default('pending');

        $table->timestamps();

        $table->unique(
            ['raffle_sequence_id', 'item_number'],
            'raffle_prizes_sequence_item_unique'
        );
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raffle_prizes');
    }
};
