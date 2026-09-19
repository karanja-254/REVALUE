<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->json('ai_metadata')->nullable()->after('condition');
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['ai_metadata', 'processing_status']);
        });
    }
};
