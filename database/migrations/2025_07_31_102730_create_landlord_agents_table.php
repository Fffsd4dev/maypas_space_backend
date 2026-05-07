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
        Schema::create('landlord_agents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone', 20);

            $table->string('id_card')->nullable();
            $table->string('selfie_photo')->nullable();
            $table->string('cac')->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_state')->nullable();
            $table->string('business_lga')->nullable();
            $table->string('about_business')->nullable();
            $table->string('business_services')->nullable();
            $table->string('business_address')->nullable();
            $table->string('logo')->nullable();
            $table->enum('verified', ['yes','no'])->default('no')->nullable();

            //Others
            $table->unsignedBigInteger('user_type_id')->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');            
            $table->unsignedBigInteger('estate_manager_id')->index();
            $table->timestamps();

            $table->foreign('user_type_id')->references('id')->on('user_types')->onDelete('cascade');
            $table->foreign('estate_manager_id')->references('id')->on('estate_managers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landlord_agents');
    }
};
