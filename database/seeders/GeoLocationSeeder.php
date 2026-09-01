<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Division;
use App\Models\District;
use App\Models\Upazila;
use App\Models\Union;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Exception;

class GeoLocationSeeder extends Seeder
{
    public function run(): void
    {
        $dataMap = [
            'divisions.json' => Division::class,
            'districts.json' => District::class,
            'upazilas.json'  => Upazila::class,
            'unions.json'    => Union::class,
        ];

        // English: Start the database transaction
        DB::beginTransaction();

        try {
            foreach ($dataMap as $file => $modelClass) {
                $path = public_path('geo_location/' . $file);

                if (!File::exists($path)) {
                    throw new Exception("File not found: {$file}");
                }

                $this->command->warn("Processing: {$file}");
                
                $json = File::get($path);
                $decoded = json_decode($json, true);

                // English: Extract table data from phpMyAdmin JSON structure
                $tableData = [];
                foreach ($decoded as $item) {
                    if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                        $tableData = $item['data'];
                        break;
                    }
                }

                if (empty($tableData)) {
                    throw new Exception("No valid data found in: {$file}");
                }

                // English: Insert in chunks for performance
                foreach (array_chunk($tableData, 500) as $chunk) {
                    $modelClass::upsert($chunk, ['id'], array_keys($chunk[0]));
                }

                // English: Fix PostgreSQL sequence immediately after each table insert
                if (DB::getDriverName() === 'pgsql') {
                    $tableName = (new $modelClass)->getTable();
                    DB::statement("SELECT setval(pg_get_serial_sequence('$tableName', 'id'), coalesce(max(id),0) + 1, false) FROM $tableName;");
                }

                $this->command->info("Finished: {$file}");
            }

            // English: If everything is successful, commit the transaction
            DB::commit();
            $this->command->info('Success! All geo-location data seeded and committed.');

        } catch (Exception $e) {
            // English: If any error occurs, rollback everything
            DB::rollBack();
            
            $this->command->error("Seeding Failed!");
            $this->command->error("Error: " . $e->getMessage());
            $this->command->warn("All changes have been rolled back. Database is clean.");
        }
    }
}