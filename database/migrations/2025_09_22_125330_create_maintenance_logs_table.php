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
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_id')
                    ->constrained('maintenance_requests')
                    ->onDelete('cascade');
            $table->foreignId('technician_id')
                    ->nullable()
                    ->constrained('technicians')
                    ->onDelete('set null'); 
            $table->text('log_message');
            $table->enum('status_update', ['open', 'in_progress', 'on_hold', 'resolved', 'closed'])->nullable();
            $table->date('visit_date')->nullable();
            $table->date('next_expected_visit_date')->nullable();
            $table->foreignId('estate_manager_id')
                  ->nullable()
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
        Schema::dropIfExists('maintenance_logs');
    }
};
