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
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->foreignId('apartment_unit_id')
                    ->constrained('apartment_units')
                    ->onDelete('cascade');
            $table->string('charge_type');
            $table->enum('fee_type', ['fixed', 'percentage']);
            $table->decimal('value', 10, 2);
            $table->foreignId('estate_manager_id')
                    ->constrained('estate_managers')
                    ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
