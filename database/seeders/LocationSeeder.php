<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cityRegions = [
            'Nicosia' => ['Central', 'North', 'South'],
            'Kyrenia' => ['Central', 'Lapta'],
            'Famagusta' => ['Central', 'Old Town'],
            'Iskele' => ['Central', 'Bafra'],
            'Morphou' => ['Central'],
        ];

        foreach ($cityRegions as $cityName => $regionNames) {
            $city = City::updateOrCreate(
                ['slug' => str($cityName)->slug()->toString()],
                ['name' => $cityName, 'is_active' => true],
            );

            foreach ($regionNames as $regionName) {
                Region::updateOrCreate(
                    ['city_id' => $city->id, 'slug' => str($regionName)->slug()->toString()],
                    ['name' => $regionName, 'is_active' => true],
                );
            }
        }
    }
}
