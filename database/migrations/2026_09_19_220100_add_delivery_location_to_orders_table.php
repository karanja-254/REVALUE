<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MAPS/LOGISTICS (Person 4) additive change to the shared `orders` table.
 *
 * These columns are the source of truth for a buyer's DELIVERY location. They
 * are all nullable so this migration is safe/additive and does not affect the
 * core orders backbone. Flag for the Core/Integration lead before merge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_address')->nullable()->after('delivery_verified_at');
            $table->decimal('delivery_latitude', 10, 7)->nullable()->after('delivery_address');
            $table->decimal('delivery_longitude', 10, 7)->nullable()->after('delivery_latitude');
            $table->string('delivery_notes')->nullable()->after('delivery_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_address',
                'delivery_latitude',
                'delivery_longitude',
                'delivery_notes',
            ]);
        });
    }
};
