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
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('apartment_id')->constrained('apartment_units')->onDelete('cascade');
            $table->foreignId('landlord_agent_id')->nullable()->constrained('landlord_agents')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'in_progress', 'on_hold', 'resolved', 'closed'])->default('Open');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('low');
            $table->string('attachment')->nullable();
            $table->date('expected_visit_date')->nullable();

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
        Schema::dropIfExists('maintenance_requests');
    }
};
