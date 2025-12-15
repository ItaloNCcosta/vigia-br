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
        Schema::create('legislatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('external_id')->unique();
            $table->unsignedSmallInteger('number')->unique()->comment('Legislature number (57, 58, etc.)');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('uri', 500)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legislatures');
    }
};
