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
        Schema::create('main_livelihood', function (Blueprint $table) {
            $table->id();
            $table->integer('farmer_profile_id')->default(0);
            $table->string('main_livelihood')->nullable();
            $table->string('meta')->nullable();
            $table->string('value')->nullable();
            $table->string('uuid')->unique(12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_livelihood');
    }
};
