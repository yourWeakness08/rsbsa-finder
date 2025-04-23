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
        Schema::create('farming_types', function (Blueprint $table) {
            $table->id();
            $table->integer('type')->default(0)->comment('0 = Crops; 1 = Livestocks; 2 = Poultry');
            $table->string('name')->nullable()->comment('Name of the crops, livestock or poultry');
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
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
        Schema::dropIfExists('farming_types');
    }
};
