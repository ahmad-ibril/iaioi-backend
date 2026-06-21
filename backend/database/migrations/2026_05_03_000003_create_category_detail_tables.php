<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chalet_details', function (Blueprint $table): void {
            $table->foreignId('listing_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('area_size')->nullable()->index();
            $table->unsignedTinyInteger('rooms_count')->nullable()->index();
            $table->unsignedTinyInteger('bathrooms_count')->nullable();
            $table->unsignedSmallInteger('max_guests')->nullable();
            $table->boolean('has_pool')->default(false)->index();
            $table->boolean('pool_is_heated')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('sports_field_details', function (Blueprint $table): void {
            $table->foreignId('listing_id')->primary()->constrained()->cascadeOnDelete();
            $table->enum('field_type', ['football', 'padel', 'basketball', 'tennis', 'other'])->index();
            $table->boolean('is_indoor')->default(false);
            $table->string('surface_type')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();
        });

        Schema::create('wedding_hall_details', function (Blueprint $table): void {
            $table->foreignId('listing_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('capacity')->nullable()->index();
            $table->string('hall_type')->nullable();
            $table->boolean('has_parking')->default(false);
            $table->boolean('has_catering')->default(false);
            $table->timestamps();
        });

        Schema::create('wedding_supply_details', function (Blueprint $table): void {
            $table->foreignId('listing_id')->primary()->constrained()->cascadeOnDelete();
            $table->enum('supply_type', ['product', 'package', 'service', 'other'])->default('product')->index();
            $table->unsignedInteger('quantity_available')->nullable();
            $table->json('package_items')->nullable();
            $table->timestamps();
        });

        Schema::create('car_rental_details', function (Blueprint $table): void {
            $table->foreignId('listing_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('car_type')->index();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('seats_count')->nullable();
            $table->boolean('with_driver')->default(false)->index();
            $table->enum('transmission', ['automatic', 'manual'])->nullable();
            $table->timestamps();
        });

        Schema::create('bus_rental_details', function (Blueprint $table): void {
            $table->foreignId('listing_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('seats_count')->index();
            $table->string('bus_type')->nullable();
            $table->boolean('with_driver')->default(true)->index();
            $table->boolean('has_ac')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_rental_details');
        Schema::dropIfExists('car_rental_details');
        Schema::dropIfExists('wedding_supply_details');
        Schema::dropIfExists('wedding_hall_details');
        Schema::dropIfExists('sports_field_details');
        Schema::dropIfExists('chalet_details');
    }
};
