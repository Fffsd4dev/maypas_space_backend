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
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('manage_properties', ['yes', 'no'])->default('no');
            $table->enum('manage_accounts', ['yes', 'no'])->default('no');
            $table->enum('manage_admins', ['yes', 'no'])->default('no');
            $table->enum('manage_estate_manager', ['yes', 'no'])->default('no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_roles');
    }
};
