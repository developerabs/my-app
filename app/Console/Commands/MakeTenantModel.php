<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MakeTenantModel extends Command
{
    protected $signature = 'make:tenant-model
                            {name : Model name}
                            {--m|migration : Create tenant migration}';

    protected $description = 'Create a Model in app/Models and optionally a tenant migration in database/migrations/tenant';

    public function handle()
    {
        $name = $this->argument('name');

        // --------------------------
        // Step 1: Create Model
        // --------------------------
        Artisan::call('make:model', [
            'name' => $name,
        ]);
        $this->info("Model created: app/Models/{$name}.php");

        // --------------------------
        // Step 2: Create Migration if requested
        // --------------------------
        if ($this->option('migration')) {

            // Plural table name auto detect
            $tableName = Str::snake(Str::pluralStudly($name));

            $migrationName = 'create_' . $tableName . '_table';
            $migrationFolder = database_path('migrations/tenant');

            // Ensure tenant migration folder exists
            if (!is_dir($migrationFolder)) {
                mkdir($migrationFolder, 0755, true);
            }

            // Generate migration inside tenant folder
            Artisan::call('make:migration', [
                'name' => $migrationName,
                '--create' => $tableName,
                '--path' => 'database/migrations/tenant',
            ]);

            $this->info("Tenant migration created in database/migrations/tenant/");
        }
    }
}
