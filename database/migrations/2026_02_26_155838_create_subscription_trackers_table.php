<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_trackers', function (Blueprint $table) {
    $table->engine = 'InnoDB'; // important

    $table->id();

    $table->foreignId('plan_id')
          ->constrained('subscription_models')
          ->cascadeOnDelete();

    $table->integer('estate_manager_id')
          ->nullable();
          

    $table->integer('created_by_admin_id')
          ->nullable();
    $table->string('status');

    $table->date('start_date');
    $table->date('end_date')->nullable();

    $table->timestamps();
    $table->softDeletes();
}); }

    public function down(): void
    {
        Schema::dropIfExists('subscription_trackers');
    }
};