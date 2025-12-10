<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            'USA',
            'Canada',
            'Australia',
        ];

        foreach ($locations as $locationName) {
            Location::firstOrCreate(
                ['name' => $locationName],
                ['name' => $locationName]
            );
        }
    }
}
