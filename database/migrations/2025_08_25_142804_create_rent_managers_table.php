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
        Schema::create('rent_managers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('occupant_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('estate_manager_id')
                  ->constrained('estate_managers')
                  ->cascadeOnDelete();

            $table->foreignId('apartment_unit_id')
                  ->constrained('apartment_units')
                  ->cascadeOnDelete();

          $table->dateTime('start_date')->index();
            $table->dateTime('termination_date')->nullable()->index(); 
            $table->boolean('is_active')->default(true)->index(); 
           $table->string('account_type', 20)->default('one-off')->index();
            $table->softDeletes();

            $table->timestamps();
            $table->index(['occupant_id', 'is_active']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_managers');
    }
};
