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
        Schema::create('half_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('signer_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict');
            $table->date('date');
            $table->enum('shift', ['morning', 'afternoon'])->default('morning');
            $table->text('reason');
            $table->text('reject_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('half_days');
    }
};
