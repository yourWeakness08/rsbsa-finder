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
        Schema::create('assistance_history', function (Blueprint $table) {
            $table->id();
            $table->integer('farmer_id')->default(0);
            $table->integer('assistance_id')->default(0);
            $table->text('livelihood')->nullable();
            $table->integer('amount')->default(0);
            $table->text('remarks')->nullable();
            $table->integer('created_by')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistance_history');
    }
};
