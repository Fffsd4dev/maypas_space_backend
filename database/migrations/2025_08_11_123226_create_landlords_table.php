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
        Schema::create('landlords', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->string('phone_number');
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->foreignId('estate_manager_id')
                  ->nullable()
                  ->constrained('estate_managers')
                  ->onDelete('set null'); // keep apartment but remove tenant link if tenant deleted
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landlords');
    }
};
