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
        Schema::create('subscription_models', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->integer('number_of_staff')->default(0);
            $table->integer('number_of_admins')->default(0);
            $table->integer('number_of_agents')->default(0);
            $table->integer('number_of_apartments')->default(0);
            $table->integer('number_of_branches')->default(0);
            $table->integer('number_of_locations')->default(0);

            $table->decimal('fee', 15, 2)->default(0.00);
           $table->decimal('discount', 5, 2)->default(0.00);

            $table->foreignId('created_by_admin_id')
                  ->constrained('admins')
                  ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_models');
    }
};