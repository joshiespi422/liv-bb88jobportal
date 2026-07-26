<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ComplianceFormReturnType;
use App\Enums\ComplianceAgency;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('compliance_forms', function (Blueprint $table) {
            $table->id();
            $table->string('agency')->default(ComplianceAgency::BIR->value);
            $table->string('code', 30);
            $table->string('name');
            $table->string('return_type')->default(ComplianceFormReturnType::MONTHLY->value);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['agency', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_forms');
    }
};
