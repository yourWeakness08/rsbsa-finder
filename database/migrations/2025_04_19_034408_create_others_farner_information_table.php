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
        Schema::create('others_farmer_information', function (Blueprint $table) {
            $table->id();
            $table->integer('farmer_id')->default(0);
            $table->integer('mothers_maiden_name')->nullable();
            $table->integer('is_household_head')->default(1)->comment('0 = No; 1 = Yes');
            $table->text('name_if_not_head')->nullable();
            $table->string('is_not_head_relationship')->nullable();
            $table->integer('no_of_living_members')->default(0);
            $table->integer('no_of_male')->default(0);
            $table->integer('no_of_female')->default(0);
            $table->string('highest_formal_education')->nullable();
            $table->integer('is_pwd')->default(0)->comment('0 = No; 1 = Yes');
            $table->integer('is_4ps')->default(0)->comment('0 = No; 1 = Yes');
            $table->integer('is_ig_mem')->default(0)->comment('0 = No; 1 = Yes');
            $table->text('is_mem_specify')->nullable();
            $table->integer('has_gov_id')->default(1)->comment('0 = No; 1 = Yes');
            $table->string('id_type')->nullable();
            $table->string('id_no')->nullable();
            $table->integer('is_farmer_coop_mem')->default(0)->comment('0 = No; 1 = Yes');
            $table->text('is_farmer_mem')->nullable();
            $table->string('contact_emergency')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('uuid')->unique(12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('others_farmer_information');
    }
};
