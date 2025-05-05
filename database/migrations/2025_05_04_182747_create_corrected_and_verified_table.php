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
        Schema::create('corrected_and_verified', function (Blueprint $table) {
            $table->id();
            $table->integer('farmer_id')->default(0);
            $table->string('paper_date')->nullable();
            $table->string('official')->nullable();
            $table->string('muni_city_official')->nullable();
            $table->string('cafc_chairman')->nullable();
            $table->string('uuid')->unique(12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corrected_and_verified');
    }
};
