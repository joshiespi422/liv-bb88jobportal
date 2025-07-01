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
        Schema::create('accomplishment_task', function (Blueprint $table) {
            // composite primary key instead of auto-increment
            $table->foreignId('accomplishment_id')->constrained('accomplishments')->onDelete('cascade');
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');

            // Set composite primary key
            $table->primary(['accomplishment_id', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accomplishment_task');
    }
};
