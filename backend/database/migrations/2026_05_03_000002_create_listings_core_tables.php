<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->string('slug')->unique();
            $table->longText('description_ar')->nullable();
            $table->longText('description_en')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('area_name_ar')->nullable();
            $table->string('area_name_en')->nullable();
            $table->string('address_ar')->nullable();
            $table->string('address_en')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->decimal('base_price', 12, 2)->nullable()->index();
            $table->char('currency_code', 3)->default('JOD');
            $table->enum('price_unit', ['hour', 'day', 'night', 'trip', 'product', 'person', 'month'])->default('day');
            $table->enum('status', ['draft', 'inactive', 'active'])->default('draft')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status', 'city_id']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('listing_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_text_ar')->nullable();
            $table->string('alt_text_en')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();

            $table->index(['listing_id', 'sort_order']);
        });

        Schema::create('listing_features', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('value_ar')->nullable();
            $table->string('value_en')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['listing_id', 'sort_order']);
        });

        Schema::create('listing_calendar_dates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['available', 'booked', 'blocked'])->default('available')->index();
            $table->decimal('price_override', 12, 2)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['listing_id', 'date']);
            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_calendar_dates');
        Schema::dropIfExists('listing_features');
        Schema::dropIfExists('listing_images');
        Schema::dropIfExists('listings');
    }
};
