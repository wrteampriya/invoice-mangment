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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            // Explicit constraint names: this server auto-names anonymous
            // foreign keys as "1", which collides with the customers table.
            $table->foreignId('user_id')->index();
            $table->foreign('user_id', 'invoices_user_id_foreign')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('customer_id');
            $table->foreign('customer_id', 'invoices_customer_id_foreign')
                ->references('id')->on('customers')->cascadeOnDelete();
            $table->string('invoice_number', 50);
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('currency', 10);
            $table->char('customer_country', 2);
            $table->string('tax_type', 50)->nullable();
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_total', 12, 2);
            $table->decimal('tax_total', 12, 2);
            $table->decimal('grand_total', 12, 2);
            $table->text('terms_and_conditions')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'invoice_number']);
            $table->index(['user_id', 'customer_id']);
            $table->index(['user_id', 'invoice_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
