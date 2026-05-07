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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->string('email')->unique();
            $table->unsignedBigInteger('role_id')->index();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('admin_roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
