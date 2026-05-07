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
    Schema::create('apartment_amenities', function (Blueprint $table) {
    $table->id();
    $table->uuid('apartment_unit_uuid');
    $table->unsignedBigInteger('amenity_id')->nullable();
    $table->unsignedBigInteger('apartment_id');
    $table->integer('amenity_number')->nullable();
    $table->timestamps();

    $table->foreign('amenity_id')
          ->references('id')->on('amenities')
          ->onDelete('set null');

    $table->foreign('apartment_id')
          ->references('id')->on('apartments')
          ->onDelete('cascade');

    $table->foreign('apartment_unit_uuid')
          ->references('uuid')->on('apartment_units')
          ->onDelete('cascade');

    $table->unique(['apartment_unit_uuid', 'amenity_id']);
    $table->unsignedBigInteger('estate_manager_id')->index();

    $table->foreign('estate_manager_id')->references('id')->on('estate_managers')->onDelete('cascade');
});

}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartment_amenities');
    }
};
