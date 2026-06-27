<?php

namespace App\Console\Commands;

use Database\Seeders\MarketplaceDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoSeed extends Command
{
    protected $signature = 'demo:seed';

    protected $description = 'Seed demo marketplace data only when the core tables are empty';

    public function handle(): int
    {
        $tables = ['countries', 'cities', 'categories', 'listings'];
        $missingTables = collect($tables)
            ->reject(fn (string $table): bool => Schema::hasTable($table));

        if ($missingTables->isNotEmpty()) {
            $this->error('Missing tables: '.$missingTables->implode(', ').'. Run php artisan migrate first.');

            return self::FAILURE;
        }

        $tablesWithData = collect($tables)
            ->filter(fn (string $table): bool => DB::table($table)->exists());

        if ($tablesWithData->isNotEmpty()) {
            $this->info('Demo data was not added because core data already exists.');

            return self::SUCCESS;
        }

        $this->call('db:seed', [
            '--class' => MarketplaceDemoSeeder::class,
            '--force' => true,
        ]);

        $this->info('Demo marketplace data added successfully.');

        return self::SUCCESS;
    }
}
