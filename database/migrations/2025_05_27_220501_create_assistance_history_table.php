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
        Schema::create('assistances', function (Blueprint $table) {
            $table->id();
            $table->integer('farmer_id')->default(0);
            $table->integer('assistance_id')->default(0);
            $table->string('reference_no')->nullable();
            $table->text('livelihood')->nullable();
            $table->integer('amount')->default(0);
            $table->text('purpose')->nullable();
            $table->string('status')->default('Pending');
            $table->integer('approved_by')->default(0);
            $table->text('approved_remarks')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->integer('cancelled_by')->default(0);
            $table->text('cancelled_remarks')->nullable();
            $table->datetime('cancelled_at')->nullable();
            $table->integer('disapproved_by')->default(0);
            $table->text('disapproved_remarks')->nullable();
            $table->datetime('disapproved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
            $table->integer('is_archived')->default(0);
            $table->integer('archived_by')->default(0);
            $table->datetime('archived_at')->nullable();
            $table->string('uuid')->unique(12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistances');
    }
};
