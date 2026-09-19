<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jiji_market_data', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('condition');
            $table->decimal('price', 12, 2);
            $table->string('source_url')->nullable();
            $table->timestamp('scraped_at');
            $table->timestamps();

            $table->index(['category', 'condition']);
            $table->index('scraped_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jiji_market_data');
    }
};
