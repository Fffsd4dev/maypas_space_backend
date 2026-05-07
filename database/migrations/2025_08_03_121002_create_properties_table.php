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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');

            $table->unsignedBigInteger('listed_by');

            $table->enum('purpose', ['rent', 'sale', 'short-let']);
            $table->enum('available', ['yes', 'no'])->default('yes');
            $table->enum('verified', ['yes', 'no'])->default('no');

            $table->string('country');
            $table->string('state');
            $table->string('locality');
            $table->string('area')->nullable();
            $table->string('street')->nullable();
            $table->string('youtube_video_link')->nullable();
            $table->string('instagram_video_link')->nullable();

            $table->foreignId('type_id')->constrained('property_types')->onDelete('cascade');
            $table->unsignedBigInteger('sub_type_id')->index();

            $table->longText('description')->nullable();
            $table->timestamps();

            $table->foreign('sub_type_id')
                ->references('id')->on('property_sub_types')
                ->onDelete('cascade');
            
            $table->foreign('listed_by')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
