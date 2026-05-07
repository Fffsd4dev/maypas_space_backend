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
        Schema::create('details', function (Blueprint $table) {
            $table->id();

            $table->string('no_rooms');
            $table->string('no_bathrooms');
            $table->string('no_toilets');

            // Area size
            $table->decimal('area_size', 8, 2)->nullable();

            // Enum fields
            $table->enum('furnished', ['yes', 'no'])->default('no');
            $table->enum('serviced', ['yes', 'no'])->default('no');
            $table->enum('newly_built', ['yes', 'no'])->default('no');

            $table->foreignId('property_id')->constrained()->onDelete('cascade');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('details');
    }
};
