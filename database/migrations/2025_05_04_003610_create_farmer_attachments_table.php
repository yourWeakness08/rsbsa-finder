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
        Schema::create('farmer_attachments', function (Blueprint $table) {
            $table->id();
            $table->integer('farmer_id')->default(0);
            $table->string('filename')->nullable();
            $table->string('filepath')->nullable();
            $table->integer('is_archived')->default(0);
            $table->integer('archived_by')->default(0);
            $table->string('archived_at')->nullable();
            $table->string('uuid')->unique(12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farmer_attachments');
    }
};
