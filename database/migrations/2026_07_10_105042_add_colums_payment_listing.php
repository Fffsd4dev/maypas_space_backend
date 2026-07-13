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
        //

        Schema::table('payment_listings', function (Blueprint $table) {
            $table->string('space_name')->nullable();
           $table->decimal('space_fee', 10, 2)->nullable();
            $table->string('space_category')->nullable();
            $table->string('booking_type')->nullable();
            $table->string('payment_status')->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('payment_listings', function (Blueprint $table) {
            $table->dropColumn('space_name');
            $table->dropColumn('space_fee');
            $table->dropColumn('space_category');
            $table->dropColumn('booking_type');
            $table->dropColumn('payment_status');

        });
    }
};
