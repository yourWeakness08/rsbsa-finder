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
        Schema::create('farm_parcels', function (Blueprint $table) {
            $table->id();
            $table->integer('farm_profile_id')->default(0);
            $table->text('brgy')->nullable();
            $table->string('city')->nullable();
            $table->float('total_farm_area')->default(0);
            $table->integer('is_whithin_ancentral_domain')->default(0)->comment('0 = No; 1 = Yes');
            $table->integer('ownership_document_no')->default(0);
            $table->integer('is_agrarian_reform_beneficiary')->default(0)->comment('0 = No; 1 = Yes');
            $table->string('ownership_type')->nullable();
            $table->text('landowner_name')->nullable();
            $table->text('is_other')->nullable();
            $table->string('uuid')->unique(12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_parcels');
    }
};
