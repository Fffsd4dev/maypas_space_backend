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
        Schema::create('tenant_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('document')
                  ->nullable()->constrained('documents')
                  ->onDelete('set null');
            $table->foreignId('for')
                  ->nullable()->constrained('users')
                  ->onDelete('set null');
            $table->foreignId('apartment_id')
                  ->nullable()->constrained('apartment_units')
                  ->onDelete('set null');        
            $table->enum('status', ['pending', 'complete'])
                   ->default('pending');
            $table->json('signed_document_json')
                  ->nullable();
            $table->dateTime('submitted_at')
                  ->nullable();
            $table->foreignId('landlord_agent_id')
                    ->nullable()->constrained('landlord_agents')
                    ->onDelete('set null');
            $table->foreignId('estate_manager_id')
                    ->nullable()->constrained('estate_managers')
                    ->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_documents');
    }
};
