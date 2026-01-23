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
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('signer_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('reason');
            $table->unsignedTinyInteger('total_hours');
            $table->text('reject_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};
