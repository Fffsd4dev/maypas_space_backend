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
        Schema::create('payment_infos', function (Blueprint $table) {
            $table->id();

            // Foreign keys and fields
            $table->foreignId('invoice_id')
                  ->constrained('invoices')
                  ->onDelete('cascade');

            $table->string('payment_name');
            $table->decimal('payment_fee', 10, 2);

            $table->foreignId('rent_managers_id')
                  ->nullable()
                  ->constrained('rent_managers')
                  ->nullOnDelete();

            $table->foreignId('rent_cycle_id')
                  ->nullable()
                  ->constrained('rent_cycles')
                  ->nullOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->foreignId('apartment_unit_id')
                  ->constrained('apartment_units')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_infos');
    }
};
