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
        Schema::create('document_signing_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('signed', ['yes', 'no'])->default('no');
            $table->foreignId('document_id')
                    ->nullable()->constrained('unsigned_documents')
                    ->onDelete('cascade');
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
        Schema::dropIfExists('document_signing_requests');
    }
};
