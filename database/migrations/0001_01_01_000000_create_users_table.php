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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            //Personal details
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('dob')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();

            //Basic details
            $table->string('nationality')->nullable();
            $table->string('state')->nullable();
            $table->text('address')->nullable();
            $table->string('identity_card')->nullable();
            $table->string('passport_photo')->nullable();

            //Contact Details
            $table->string('phone', 20);
            $table->string('other_phone', 20)->nullable();
            $table->string('email');
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_number', 20)->nullable();
            $table->string('emergency_contact_email')->nullable();

            //next of Kin
            $table->string('next_of_kin_name')->nullable();
            $table->text('next_of_kin_address')->nullable();
            $table->string('next_of_kin_email')->nullable();
            $table->string('next_of_kin_number', 20)->nullable();

            //Others
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');            
            $table->enum('deactivated', ['yes', 'no'])->default('no');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
