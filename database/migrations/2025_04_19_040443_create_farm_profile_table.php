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
        Schema::create('farm_profile', function (Blueprint $table) {
            $table->id();
            $table->integer('farmer_id')->default(0);
            $table->string('main_livelihood')->nullable()->comment('serialized data that can choose multiple main livelihood');
            $table->float('farming_gross')->default(0);
            $table->float('no_farming_gross')->default(0);
            $table->integer('farm_parcel_no')->default(0);
            $table->text('farmer_in_rotation_name')->nullable();
            $table->string('uuid')->unique(12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_profile');
    }
};
