<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_availability_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('slot_name');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->enum('status', ['available', 'reserved', 'unavailable', 'pending'])
                ->default('available')
                ->index();
            $table->timestamps();

            $table->index(['listing_id', 'date', 'status']);
            $table->index(['listing_id', 'date', 'start_time', 'end_time']);
        });

        if (Schema::hasTable('booking_requests')) {
            $this->normalizeBookingStatusColumn();

            Schema::table('booking_requests', function (Blueprint $table): void {
                if (! Schema::hasColumn('booking_requests', 'availability_slot_id')) {
                    $table
                        ->foreignId('availability_slot_id')
                        ->nullable()
                        ->after('listing_id')
                        ->constrained('listing_availability_slots')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('booking_requests', 'customer_name')) {
                    $table->string('customer_name')->nullable()->after('status');
                }

                if (! Schema::hasColumn('booking_requests', 'customer_phone')) {
                    $table->string('customer_phone', 40)->nullable()->after('customer_name');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('booking_requests')) {
            Schema::table('booking_requests', function (Blueprint $table): void {
                if (Schema::hasColumn('booking_requests', 'availability_slot_id')) {
                    $table->dropConstrainedForeignId('availability_slot_id');
                }

                if (Schema::hasColumn('booking_requests', 'customer_phone')) {
                    $table->dropColumn('customer_phone');
                }

                if (Schema::hasColumn('booking_requests', 'customer_name')) {
                    $table->dropColumn('customer_name');
                }
            });
        }

        Schema::dropIfExists('listing_availability_slots');
    }

    private function normalizeBookingStatusColumn(): void
    {
        try {
            match (DB::getDriverName()) {
                'mysql', 'mariadb' => DB::statement(
                    "ALTER TABLE booking_requests MODIFY status VARCHAR(32) NOT NULL DEFAULT 'pending'",
                ),
                'pgsql' => $this->normalizePostgresStatusColumn(),
                default => null,
            };
        } catch (Throwable) {
            // SQLite and some local databases do not need an enum rewrite here.
        }
    }

    private function normalizePostgresStatusColumn(): void
    {
        DB::statement("ALTER TABLE booking_requests ALTER COLUMN status TYPE VARCHAR(32) USING status::VARCHAR");
        DB::statement("ALTER TABLE booking_requests ALTER COLUMN status SET DEFAULT 'pending'");
    }
};
