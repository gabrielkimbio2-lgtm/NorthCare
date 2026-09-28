<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['General consultation', 'Dental examination', 'Teeth cleaning', 'Psychotherapy', 'Nutrition consultation', 'Chiropractic adjustment'] as $serviceName) {
            Service::updateOrCreate(
                ['slug' => str($serviceName)->slug()->toString()],
                ['name' => $serviceName, 'is_active' => true],
            );
        }
    }
}
