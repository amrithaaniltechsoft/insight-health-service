<?php

use App\Models\SubCategory;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Helper to add service names as subcategories if not already present
        $addMissingForCategory = function (int $categoryId) {
            $existing = SubCategory::where('category_id', $categoryId)
                ->pluck('name')
                ->map(fn($n) => strtolower(trim($n)))
                ->toArray();

            $services = Service::where('category_id', $categoryId)->get();
            $maxOrder = SubCategory::where('category_id', $categoryId)->max('order') ?? 0;

            foreach ($services as $s) {
                $name = $s->title ?? $s->service_name;
                if (!in_array(strtolower(trim($name)), $existing)) {
                    $maxOrder++;
                    SubCategory::create([
                        'category_id' => $categoryId,
                        'name' => $name,
                        'order' => $maxOrder,
                    ]);
                }
            }
        };

        $addMissingForCategory(1); // Pregnancy Ultrasound Scans
        $addMissingForCategory(2); // General Ultrasound Scans
        $addMissingForCategory(3); // Physiotherapy
    }

    public function down(): void
    {
        // Data migration — no reliable way to reverse without tracking IDs.
        // Manually delete rows if needed.
    }
};
