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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
            //$table->foreignId('category_id')->constrained('complaint_categories')->onDelete('cascade');
            $table->foreignId('apartment_id')->constrained('apartment_units')->onDelete('cascade');
            $table->foreignId('landlord_agent_id')->nullable()->constrained('landlord_agents')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['open', 'under_review', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('evidence')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('estate_manager_id')
                  ->nullable()
                  ->constrained('estate_managers')
                  ->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
