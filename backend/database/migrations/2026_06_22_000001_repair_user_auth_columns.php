<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->unique()->after('email');
            }

            if (! Schema::hasColumn('users', 'auth_provider')) {
                $table->string('auth_provider', 32)->default('email')->after('google_id');
            }

            if (! Schema::hasColumn('users', 'account_type')) {
                $table->string('account_type')->default('regular_user')->after('password')->index();
            }

            if (! Schema::hasColumn('users', 'verification_status')) {
                $table->string('verification_status', 32)->default('none')->after('account_type')->index();
            }
        });
    }

    public function down(): void
    {
        // This repair migration must not remove columns that may predate it.
    }
};
