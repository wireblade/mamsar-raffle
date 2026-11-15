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
    Schema::table('raffle_sequences', function (Blueprint $table) {
        $table->unsignedInteger('allocation_weight')
            ->nullable()
            ->after('allocation_type');
    });
}

public function down(): void
{
    Schema::table('raffle_sequences', function (Blueprint $table) {
        $table->dropColumn('allocation_weight');
    });
}

};
