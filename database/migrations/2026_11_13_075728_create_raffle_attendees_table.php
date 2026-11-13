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
        Schema::create('raffle_attendees', function (Blueprint $table) {
        $table->id();

        $table->foreignId('raffle_event_id')
            ->constrained('raffle_events')
            ->cascadeOnDelete();

        $table->string('raffle_number', 50);

        $table->timestamps();

        $table->unique(
            ['raffle_event_id', 'raffle_number'],
            'raffle_attendees_event_number_unique'
        );
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raffle_attendees');
    }
};
