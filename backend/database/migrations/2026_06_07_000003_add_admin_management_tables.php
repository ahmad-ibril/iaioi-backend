<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeRoleAndListingStatusColumns();

        Schema::table('listings', function (Blueprint $table): void {
            if (! Schema::hasColumn('listings', 'featured_until')) {
                $table->timestamp('featured_until')->nullable()->after('is_featured')->index();
            }
        });

        Schema::create('city_areas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['city_id', 'name_ar']);
        });

        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('group')->default('general')->index();
            $table->string('value_type', 32)->default('string');
            $table->timestamps();
        });

        Schema::create('app_banners', function (Blueprint $table): void {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->string('subtitle_ar')->nullable();
            $table->string('subtitle_en')->nullable();
            $table->string('image_url')->nullable();
            $table->string('link_url')->nullable();
            $table->string('placement')->default('home')->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_banners');
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('city_areas');

        Schema::table('listings', function (Blueprint $table): void {
            if (Schema::hasColumn('listings', 'featured_until')) {
                $table->dropColumn('featured_until');
            }
        });
    }

    private function normalizeRoleAndListingStatusColumns(): void
    {
        try {
            match (DB::getDriverName()) {
                'mysql', 'mariadb' => $this->normalizeMysqlColumns(),
                'pgsql' => $this->normalizePostgresColumns(),
                default => null,
            };
        } catch (Throwable) {
            // SQLite/local fallback can continue with the existing column type.
        }
    }

    private function normalizeMysqlColumns(): void
    {
        DB::statement("ALTER TABLE users MODIFY role VARCHAR(32) NOT NULL DEFAULT 'customer'");
        DB::statement("ALTER TABLE listings MODIFY status VARCHAR(32) NOT NULL DEFAULT 'draft'");
    }

    private function normalizePostgresColumns(): void
    {
        DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(32) USING role::VARCHAR");
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'customer'");
        DB::statement("ALTER TABLE listings ALTER COLUMN status TYPE VARCHAR(32) USING status::VARCHAR");
        DB::statement("ALTER TABLE listings ALTER COLUMN status SET DEFAULT 'draft'");
    }
};
