<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_details', function (Blueprint $table): void {
            $table->foreignId('listing_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stars')->nullable()->index();
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->json('services')->nullable();
            $table->timestamps();
        });

        Schema::create('hotel_rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hotel_listing_id')->constrained('listings')->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('room_type')->nullable()->index();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->unsignedTinyInteger('capacity_adults')->default(2);
            $table->unsignedTinyInteger('capacity_children')->default(0);
            $table->decimal('price_per_night', 12, 2)->nullable()->index();
            $table->char('currency_code', 3)->default('JOD');
            $table->unsignedSmallInteger('total_rooms')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hotel_room_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hotel_room_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_text_ar')->nullable();
            $table->string('alt_text_en')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();
        });

        Schema::create('hotel_room_calendar_dates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hotel_room_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['available', 'booked', 'blocked'])->default('available')->index();
            $table->unsignedSmallInteger('available_quantity')->nullable();
            $table->decimal('price_override', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['hotel_room_id', 'date']);
            $table->index(['date', 'status']);
        });

        Schema::create('tourism_program_details', function (Blueprint $table): void {
            $table->foreignId('listing_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('destination_country')->index();
            $table->string('destination_city')->nullable()->index();
            $table->string('departure_country')->nullable();
            $table->string('departure_city')->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable()->index();
            $table->date('trip_date')->nullable()->index();
            $table->enum('trip_type', ['domestic', 'international'])->index();
            $table->unsignedSmallInteger('seats_available')->nullable();
            $table->json('included_services')->nullable();
            $table->json('flight_times')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tourism_program_details');
        Schema::dropIfExists('hotel_room_calendar_dates');
        Schema::dropIfExists('hotel_room_images');
        Schema::dropIfExists('hotel_rooms');
        Schema::dropIfExists('hotel_details');
    }
};
