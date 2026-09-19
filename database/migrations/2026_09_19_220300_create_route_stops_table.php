<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shared `route_stops` table (owned by Maps/Logistics, Person 4).
 *
 * An ordered stop on a route. Each stop references an order and is either a
 * PICKUP (from the seller) or a DELIVERY (to the buyer). The physical
 * location for the stop is derived from the related listing (pickup) or
 * order (delivery), so it is never duplicated here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('type'); // pickup | delivery
            $table->unsignedInteger('sequence')->default(0);
            $table->string('status')->default('pending');
            $table->string('verification_result')->nullable(); // matched | mismatch (pickup only)
            $table->text('verification_notes')->nullable();
            $table->string('evidence_path')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['route_id', 'sequence']);
            $table->index(['order_id', 'type']);
            $table->index('status');
            // A given order can only have one pickup and one delivery stop.
            $table->unique(['order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_stops');
    }
};
