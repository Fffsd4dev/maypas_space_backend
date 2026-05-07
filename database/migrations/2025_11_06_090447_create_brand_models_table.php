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
        Schema::create('brand_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('addresses');
            $table->json('phones');
            $table->json('social_links');
            $table->string('logo');
            $table->foreignId('estate_manager_id')->references('id')->on('estate_managers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_models');
    }
};
