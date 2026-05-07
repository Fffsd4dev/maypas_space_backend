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
        Schema::create('signed_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('filename');
            $table->string('signer_name');
            $table->string('ip_address');
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
        Schema::dropIfExists('signed_documents');
    }
};
