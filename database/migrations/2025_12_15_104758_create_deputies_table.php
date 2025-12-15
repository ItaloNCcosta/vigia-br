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
        Schema::create('deputies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('external_id')->unique();
            $table->foreignUuid('legislature_id')
                ->nullable()
                ->constrained('legislatures')
                ->nullOnDelete();
            $table->foreignUuid('party_id')
                ->nullable()
                ->constrained('parties')
                ->nullOnDelete();
            $table->string('name', 200);
            $table->string('civil_name', 200)->nullable();
            $table->string('electoral_name', 200)->nullable();
            $table->string('cpf', 14)->nullable();
            $table->char('gender', 1)->nullable()->comment('M or F');
            $table->date('birth_date')->nullable();
            $table->string('birth_city', 100)->nullable();
            $table->char('birth_state', 2)->nullable();
            $table->date('death_date')->nullable();
            $table->string('education_level', 100)->nullable();
            $table->char('state_code', 2)->comment('UF representation');
            $table->string('party_acronym', 20)->comment('Denormalized for fast queries');
            $table->string('status', 50)->nullable()->comment('Exercício, Afastado, etc.');
            $table->string('email', 100)->nullable();
            $table->string('photo_url', 500)->nullable();
            $table->string('website_url', 500)->nullable();
            $table->json('social_links')->nullable();
            $table->string('uri', 500)->nullable()->comment('API URI');
            $table->json('office')->nullable()->comment('Gabinete data');
            $table->decimal('total_expenses', 15, 2)->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->index('state_code');
            $table->index('party_acronym');
            $table->index(['state_code', 'party_acronym']);
            $table->index('total_expenses');
            $table->index('status');
            $table->index('last_synced_at');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deputies');
    }
};
