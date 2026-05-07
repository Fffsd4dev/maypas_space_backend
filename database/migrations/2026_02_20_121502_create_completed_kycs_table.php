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
        Schema::create('completed_kycs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')
                    ->nullable()->constrained('users')
                    ->onDelete('cascade');
            $table->enum('verified', ['yes', 'no'])->default('no');
            $table->foreignId('verified_by')
                    ->nullable()->constrained('landlord_agents')
                    ->onDelete('cascade');
            $table->enum('queried', ['yes', 'no'])->default('no');
            $table->foreignId('queried_by')
                    ->nullable()->constrained('landlord_agents')
                    ->onDelete('cascade');
            $table->text('reason')->nullable();
            $table->foreignId('estate_manager_id')
                    ->nullable()->constrained('estate_managers')
                    ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('completed_kycs');
    }
};
