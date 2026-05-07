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
        Schema::create('rent_cycles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('occupant_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('rent_manager_id')
                  ->constrained('rent_managers')
                  ->cascadeOnDelete();

           $table->dateTime('cycle_start_date')->index();
          $table->dateTime('cycle_end_date')->index();
          $table->dateTime('cycle_start_date_server_time')->nullable()->index();
          $table->dateTime('cycle_end_date_server_time')->nullable()->index();
          $table->string('status')->default('active')->index();
          $table->decimal('fee', 10, 2);
          $table->boolean('is_paid')->default(false)->index();

            $table->timestamps();
             $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_cycles');
    }
};
