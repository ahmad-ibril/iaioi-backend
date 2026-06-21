<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('account_type')->default('regular_user')->after('password')->index();
            $table->enum('verification_status', ['none', 'pending', 'verified', 'rejected'])
                ->default('none')
                ->after('account_type')
                ->index();
        });

        Schema::table('listings', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->enum('listing_type', ['offer'])->default('offer')->after('status')->index();
        });

        DB::table('listings')
            ->whereNull('user_id')
            ->whereNotNull('owner_user_id')
            ->update(['user_id' => DB::raw('owner_user_id')]);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE listings MODIFY status ENUM('draft','inactive','active','pending','rejected') NOT NULL DEFAULT 'draft'");
        }

        Schema::create('wanted_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->decimal('budget', 12, 2)->nullable()->index();
            $table->foreignId('region_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('area_name')->nullable();
            $table->date('needed_date')->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->enum('status', ['active', 'pending', 'rejected'])->default('active')->index();
            $table->enum('request_type', ['wanted'])->default('wanted')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'region_id', 'status']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('wanted_request_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wanted_request_id')->constrained()->cascadeOnDelete();
            $table->enum('media_type', ['image', 'video'])->default('image')->index();
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['wanted_request_id', 'media_type', 'sort_order']);
        });

        Schema::create('wanted_request_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wanted_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_filter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key');
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 12, 2)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('value_date')->nullable();
            $table->time('value_time')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['wanted_request_id', 'key']);
            $table->index(['category_filter_id', 'key']);
            $table->index(['key', 'value_number']);
            $table->index(['key', 'value_boolean']);
            $table->index(['key', 'value_date']);
        });

        Schema::create('verification_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_name');
            $table->string('business_type');
            $table->string('commercial_registration_number')->nullable();
            $table->string('license_number')->nullable();
            $table->string('national_id_image')->nullable();
            $table->string('commercial_registration_image')->nullable();
            $table->string('business_license_image')->nullable();
            $table->string('ownership_or_rent_contract_image')->nullable();
            $table->decimal('business_location_latitude', 10, 7)->nullable();
            $table->decimal('business_location_longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_requests');
        Schema::dropIfExists('wanted_request_attribute_values');
        Schema::dropIfExists('wanted_request_media');
        Schema::dropIfExists('wanted_requests');

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE listings MODIFY status ENUM('draft','inactive','active') NOT NULL DEFAULT 'draft'");
        }

        Schema::table('listings', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'listing_type']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['account_type', 'verification_status']);
        });
    }
};
