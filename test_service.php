<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking service and sub-category data...\n";

$services = App\Models\Service::all();
echo "Total services: " . $services->count() . "\n";

foreach($services as $service) {
    echo "Service ID: " . $service->id . ", Name: " . $service->service_name . ", sub_category_id: " . ($service->sub_category_id ?? 'null') . "\n";
}

$subCategories = App\Models\SubCategory::all();
echo "\nTotal sub-categories: " . $subCategories->count() . "\n";

foreach($subCategories as $sub) {
    echo "Sub-category ID: " . $sub->id . ", Name: " . $sub->name . ", category_id: " . $sub->category_id . "\n";
}

echo "\nDone\n";
