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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->string('contact_person');
            $table->string('email');
            $table->string('phone');
            $table->string('location');
            $table->text('registration_details')->nullable();
            $table->string('website')->nullable();
            $table->string('supporting_document_path')->nullable();
            $table->string('verification_status')->default('pending');
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['type', 'verification_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
