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
        Schema::create('assistance', function (Blueprint $table) {
            $table->id();
            $table->string('livelihoods')->nullable();
            $table->string('name')->nullable();
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
            $table->integer('is_archived')->default(0);
            $table->integer('archived_by')->default(0);
            $table->string('uuid')->unique(12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistance');
    }
};
