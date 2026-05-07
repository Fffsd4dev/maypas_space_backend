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
        Schema::create('other_documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('filename');
            $table->foreignId('tenant_id')
                    ->nullable()->constrained('users')
                    ->onDelete('cascade');
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
        Schema::dropIfExists('other_documents');
    }
};
