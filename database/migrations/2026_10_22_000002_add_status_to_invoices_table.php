<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('invoices', 'status')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('status', 20)->default('sent')->after('customer_country');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('invoices', 'status')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
