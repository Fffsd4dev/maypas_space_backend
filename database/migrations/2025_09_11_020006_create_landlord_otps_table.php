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
        Schema::create('landlord_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_agent_id')->constrained()->onDelete('cascade');
            $table->string('code');
            $table->enum('type', ['email_verification', 'password_reset'])->default('email_verification');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landlord_otps');
    }
};
