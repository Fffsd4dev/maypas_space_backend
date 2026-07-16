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
        Schema::table('payment_listings', function (Blueprint $table) {

            $table->foreignId('refund_invoice_id')
                ->nullable()
                ->after('payment_by_user_id')
                ->constrained('invoices')
                ->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_listings', function (Blueprint $table) {

            $table->dropForeign(['refund_invoice_id']);
            $table->dropColumn('refund_invoice_id');

        });
    }
};