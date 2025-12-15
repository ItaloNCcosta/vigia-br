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
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('deputy_id')
                ->constrained('deputies')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('external_id');

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            $table->string('expense_type', 200)->nullable();

            $table->string('document_type', 100)->nullable();
            $table->unsignedTinyInteger('document_type_code')->nullable();
            $table->string('document_number', 50)->nullable();
            $table->date('document_date')->nullable();
            $table->string('document_url', 500)->nullable();

            $table->decimal('document_value', 12, 2)->default(0);
            $table->decimal('net_value', 12, 2)->default(0);
            $table->decimal('disallowed_value', 12, 2)->default(0);

            $table->string('supplier_name', 200)->nullable();
            $table->string('supplier_document', 20)->nullable();

            $table->string('reimbursement_number', 50)->nullable();
            $table->unsignedBigInteger('batch_code')->nullable();
            $table->unsignedSmallInteger('installment')->default(0);

            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['deputy_id', 'external_id']);

            $table->index(['deputy_id', 'year', 'month']);
            $table->index('expense_type');
            $table->index('document_date');
            $table->index('supplier_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
