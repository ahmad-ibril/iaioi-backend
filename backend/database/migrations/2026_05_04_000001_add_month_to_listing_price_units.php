<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE listings MODIFY price_unit ENUM('hour', 'day', 'night', 'trip', 'product', 'person', 'month') NOT NULL DEFAULT 'day'");
    }

    public function down(): void
    {
        DB::table('listings')
            ->where('price_unit', 'month')
            ->update(['price_unit' => 'day']);

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE listings MODIFY price_unit ENUM('hour', 'day', 'night', 'trip', 'product', 'person') NOT NULL DEFAULT 'day'");
    }
};
