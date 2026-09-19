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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('item_price', 12, 2);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('service_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('payment_reference')->nullable()->unique();
            $table->string('payment_status')->default('pending');
            $table->string('order_status')->default('pending_payment');
            $table->string('pickup_pin', 4)->nullable();
            $table->string('delivery_pin', 4)->nullable();
            $table->timestamp('pickup_verified_at')->nullable();
            $table->timestamp('delivery_verified_at')->nullable();
            $table->timestamps();

            $table->index('payment_status');
            $table->index('order_status');
            $table->index(['buyer_id', 'order_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
