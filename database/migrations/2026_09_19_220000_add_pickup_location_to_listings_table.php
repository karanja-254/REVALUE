<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MAPS/LOGISTICS (Person 4) additive change to the shared `listings` table.
 *
 * These columns are the source of truth for a seller's PICKUP location. They
 * are all nullable so this migration is safe/additive and does not affect the
 * core listings backbone. Flag for the Core/Integration lead before merge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('pickup_address')->nullable()->after('image_path');
            $table->decimal('pickup_latitude', 10, 7)->nullable()->after('pickup_address');
            $table->decimal('pickup_longitude', 10, 7)->nullable()->after('pickup_latitude');
            $table->string('pickup_notes')->nullable()->after('pickup_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_address',
                'pickup_latitude',
                'pickup_longitude',
                'pickup_notes',
            ]);
        });
    }
};
