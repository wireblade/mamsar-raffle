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
         Schema::create('raffle_events', function (Blueprint $table) {
        $table->id();

        $table->string('name');

        $table->date('event_date')->nullable();

        $table->enum('status', [
            'draft',
            'registration',
            'ready',
            'ongoing',
            'completed',
        ])->default('draft');

        $table->unsignedInteger('total_attendees')->default(0);

        $table->timestamp('registration_closed_at')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raffle_events');
    }
};
