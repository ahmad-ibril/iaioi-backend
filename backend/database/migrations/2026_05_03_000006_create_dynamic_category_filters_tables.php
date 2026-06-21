<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
            $table->string('group_key')->nullable()->after('slug')->index();
            $table->boolean('supports_booking')->default(true)->after('icon')->index();
            $table->json('settings')->nullable()->after('supports_booking');
            $table->softDeletes();
        });

        Schema::create('category_filters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label_ar');
            $table->string('label_en')->nullable();
            $table->enum('input_type', ['text', 'number', 'boolean', 'select', 'multi_select', 'date', 'time', 'rating'])->default('text');
            $table->json('options')->nullable();
            $table->string('unit_ar')->nullable();
            $table->string('unit_en')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(true)->index();
            $table->boolean('is_sortable')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['category_id', 'key']);
            $table->index(['category_id', 'is_filterable', 'sort_order']);
        });

        Schema::create('listing_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_filter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key');
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 12, 2)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('value_date')->nullable();
            $table->time('value_time')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['listing_id', 'key']);
            $table->index(['category_filter_id', 'key']);
            $table->index(['key', 'value_number']);
            $table->index(['key', 'value_boolean']);
            $table->index(['key', 'value_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_attribute_values');
        Schema::dropIfExists('category_filters');

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'parent_id',
                'group_key',
                'supports_booking',
                'settings',
                'deleted_at',
            ]);
        });
    }
};
