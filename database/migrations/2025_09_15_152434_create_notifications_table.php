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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->json('data');   
            $table->foreignId('apartment_id')
                  ->constrained('apartment_units')
                  ->onDelete('cascade');
            $table->foreignId('for')
                  ->constrained('landlord_agents')
                  ->onDelete('cascade');
            $table->foreignId('estate_manager_id')
                  ->constrained('estate_managers')
                  ->onDelete('cascade');
            $table->enum('is_read', ['yes', 'no'])->default('no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
