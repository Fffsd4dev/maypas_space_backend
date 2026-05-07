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
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->foreignId('category_id')
                  ->constrained('apartment_categories')
                  ->onDelete('cascade'); // delete apartments if category deleted
            $table->integer('number_item');
            $table->string('location');
            $table->string('address');
            $table->foreignId('landlord_id')
                  ->nullable()
                  ->constrained('landlords')
                  ->onDelete('set null');
            $table->foreignId('country_id')
                  ->nullable()
                  ->constrained('countries')
                  ->onDelete('set null');
            $table->foreignId('landlord_agent_id')
                    ->nullable()->constrained('landlord_agents')
                    ->onDelete('set null');
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
        Schema::dropIfExists('apartments');
    }
};
