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
        Schema::create('attendance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('salary_period_id')->constrained('salary_periods')->onDelete('restrict');
            $table->unsignedTinyInteger('day')->default(0);
            $table->decimal('overtime', 5, 2)->default(0);
            $table->unsignedTinyInteger('absent')->default(0);
            $table->unsignedTinyInteger('halfday')->default(0);
            $table->decimal('lates', 5, 2)->default(0);
            $table->decimal('total', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'salary_period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_reports');
    }
};
