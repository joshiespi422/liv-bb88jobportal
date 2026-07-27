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
        Schema::create('compliance_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_compliance_form_id')->constrained()->restrictOnDelete();
            $table->year('year');
            $table->unsignedTinyInteger('period');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('document');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['company_compliance_form_id', 'year', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_uploads');
    }
};
