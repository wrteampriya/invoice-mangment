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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            // Explicit constraint name: this server auto-names anonymous
            // foreign keys as "1", which is already in use.
            $table->foreignId('invoice_id');
            $table->foreign('invoice_id', 'invoice_items_invoice_id_foreign')
                ->references('id')->on('invoices')->cascadeOnDelete();
            $table->text('item_description');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 12, 2);
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
