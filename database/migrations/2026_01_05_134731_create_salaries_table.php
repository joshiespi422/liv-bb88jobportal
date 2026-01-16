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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict');
            $table->foreignId('salary_period_id')->constrained('salary_periods')->onDelete('restrict');
            $table->decimal('rate_day', 10, 2);
            $table->decimal('rate_month', 10, 2);
            $table->unsignedTinyInteger('absent_day')->nullable();
            $table->decimal('absent_deduction', 10, 2)->nullable();
            $table->unsignedTinyInteger('overtime_hour')->nullable();
            $table->decimal('overtime_amount', 10, 2)->nullable();
            $table->decimal('gross_pay', 10, 2);
            $table->decimal('net_pay', 10, 2);
            $table->timestamps();

            $table->unique(['user_id', 'salary_period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
