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
        Schema::create('unsigned_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('filename');
            $table->enum('type', ['pdf', 'image']);
            $table->foreignId('apartment_id')
                    ->nullable()->constrained('apartments')
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
        Schema::dropIfExists('unsigned_documents');
    }
};
