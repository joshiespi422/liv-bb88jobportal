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
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('signed_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('department_id')->constrained('departments')->onDelete('restrict');
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict');
            $table->text('purpose');
            $table->string('name');
            $table->text('description');
            $table->unsignedTinyInteger('quantity');
            $table->decimal('amount', 10, 2);
            $table->date('date_needed');
            $table->text('remarks')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_requests');
    }
};
