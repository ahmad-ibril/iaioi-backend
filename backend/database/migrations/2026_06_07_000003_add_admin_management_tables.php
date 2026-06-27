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

        if (Schema::hasTable('listings')) {
            Schema::table('listings', function (Blueprint $table): void {
                if (! Schema::hasColumn('listings', 'featured_until')) {
                    $table->timestamp('featured_until')->nullable()->after('is_featured')->index();
                }
            });
        }

        if (! Schema::hasTable('city_areas')) {
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
        }

        if (! Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->string('group')->default('general')->index();
                $table->string('value_type', 32)->default('string');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('app_banners')) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('app_banners');
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('city_areas');

        if (Schema::hasTable('listings')) {
            Schema::table('listings', function (Blueprint $table): void {
                if (Schema::hasColumn('listings', 'featured_until')) {
                    $table->dropColumn('featured_until');
                }
            });
        }
    }

    private function normalizeRoleAndListingStatusColumns(): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => $this->normalizeMysqlColumns(),
            'pgsql' => $this->normalizePostgresColumns(),
            default => null,
        };
    }

    private function normalizeMysqlColumns(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            $this->runSafely("ALTER TABLE users MODIFY role VARCHAR(32) NOT NULL DEFAULT 'customer'");
        }

        if (Schema::hasTable('listings') && Schema::hasColumn('listings', 'status')) {
            $this->runSafely("ALTER TABLE listings MODIFY status VARCHAR(32) NOT NULL DEFAULT 'draft'");
        }
    }

    private function normalizePostgresColumns(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            $this->runSafely('ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(32) USING role::VARCHAR');
            $this->runSafely("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'customer'");
        }

        if (Schema::hasTable('listings') && Schema::hasColumn('listings', 'status')) {
            $this->runSafely('ALTER TABLE listings ALTER COLUMN status TYPE VARCHAR(32) USING status::VARCHAR');
            $this->runSafely("ALTER TABLE listings ALTER COLUMN status SET DEFAULT 'draft'");
        }
    }

    private function runSafely(string $statement): void
    {
        try {
            DB::statement($statement);
        } catch (Throwable) {
            // Keep deployment migrations moving when a legacy schema already matches.
        }
    }
};
