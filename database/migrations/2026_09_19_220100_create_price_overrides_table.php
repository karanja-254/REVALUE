<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users');
            $table->decimal('old_price', 12, 2);
            $table->decimal('new_price', 12, 2);
            $table->text('reason')->nullable();
            $table->timestamp('override_at')->useCurrent();
            $table->timestamps();

            $table->index('listing_id');
            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_overrides');
    }
};
