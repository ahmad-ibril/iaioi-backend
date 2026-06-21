<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->enum('media_type', ['image', 'video'])->default('image')->index();
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->string('alt_text_ar')->nullable();
            $table->string('alt_text_en')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();

            $table->index(['listing_id', 'media_type', 'sort_order']);
        });

        Schema::create('booking_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['new', 'in_review', 'confirmed', 'rejected', 'cancelled'])
                ->default('new')
                ->index();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['listing_id', 'status', 'date_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_requests');
        Schema::dropIfExists('listing_media');
    }
};
