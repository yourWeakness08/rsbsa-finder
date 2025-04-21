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
        Schema::create('farm_parcel_informations', function (Blueprint $table) {
            $table->id();
            $table->integer('farm_parcels_id')->default(0);
            $table->string('farming_type');
            $table->float('size')->default(0);
            $table->integer('no_of_head')->default(0);
            $table->integer('farm_type')->default(0)->comment('1 = Irrigated; 2 = Rainfed Upland; 3 = Rainfed Lowland');
            $table->integer('is_organic_practitioner')->default(0)->comment('0 = No; 1 = Yes');
            $table->text('remarks')->nullable();
            $table->string('uuid')->unique(12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_parcel_informations');
    }
};
