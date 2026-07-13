<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        SubCategory::truncate();
        Category::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $categoriesData = [
            [
                'name' => 'Pregnancy Ultrasound Scans',
                'slug' => 'pregnancy-scans',
                'description' => 'Pregnancy Ultrasound Scans category',
                'promo_title' => 'Not sure which scan?',
                'promo_description' => 'Use our scan calculator to find the right package for your exact gestation week.',
                'promo_link_text' => 'Find my scan →',
                'promo_link_href' => '/serviceslisting/pregnancy-scans#scan-calculator',
                'promo_bg_type' => 'pearl',
                'subcategories' => [
                    'Early Reassurance Scan',
                    'Dating Scan',
                    'Baby Gender Scan',
                    '4D Ultrasound Packages',
                    'Anomaly Scan',
                    'Growth & Presentation Scan',
                ]
            ],
            [
                'name' => 'General Ultrasound Scans',
                'slug' => 'diagnostics',
                'description' => 'General Ultrasound Scans category',
                'promo_title' => 'Clinical Blood Tests',
                'promo_description' => 'We offer comprehensive private blood profiling, from routine health checks to specialized fertility and hormone panels. Results returned rapidly without the NHS wait.',
                'promo_link_text' => 'View all blood tests →',
                'promo_link_href' => '/serviceslisting/blood-tests',
                'promo_bg_type' => 'zinc',
                'subcategories' => [
                    'General Abdominal',
                    'Kidney, Ureter & Bladder',
                    'Deep Vein Thrombosis (DVT)',
                    'Well Woman Pelvic',
                    'Well Man Screening',
                    'Aortic Surveillance (AAA)',
                ]
            ],
            [
                'name' => 'Physiotherapy',
                'slug' => 'physiotherapy',
                'description' => 'Physiotherapy category',
                'promo_title' => null,
                'promo_description' => null,
                'promo_link_text' => null,
                'promo_link_href' => null,
                'promo_bg_type' => 'pearl',
                'subcategories' => [
                    'Manual Physiotherapy',
                    'Whiplash Injury Rehab',
                    'Exercise Rehabilitation',
                    'Ultrasound Guided Injections',
                    'Steroid Injection Therapy',
                    'Clinical Acupuncture',
                ]
            ],
            [
                'name' => 'Blood Tests',
                'slug' => 'blood-tests',
                'description' => 'Blood Tests category',
                'promo_title' => 'Rapid Laboratory Results',
                'promo_description' => 'Most of our comprehensive blood profiles and tests are processed with rapid turnaround times, directly from our accredited partner laboratories.',
                'promo_link_text' => 'Learn more about our lab →',
                'promo_link_href' => '/blood-tests#scan-calculator',
                'promo_bg_type' => 'pearl',
                'subcategories' => [
                    'General Health',
                    'Heart & Cardio',
                    'Fertility & Hormones',
                    'Men\'s Health',
                    'Women\'s Health',
                    'Infection & Immune',
                    'Organ Function',
                ]
            ],
            [
                'name' => 'Other Diagnostics',
                'slug' => 'other-diagnostics',
                'description' => 'Other Diagnostics category',
                'promo_title' => 'Preventive Care',
                'promo_description' => 'Advanced screening services designed to detect early indicators and help support your long-term health and wellness.',
                'promo_link_text' => 'Contact Us to Learn More →',
                'promo_link_href' => '/contact',
                'promo_bg_type' => 'zinc',
                'subcategories' => [
                    'Cervical Screening - Coming Soon!',
                    'Health MOT - Coming Soon!',
                ]
            ],
        ];

        foreach ($categoriesData as $data) {
            $subcategories = $data['subcategories'];
            unset($data['subcategories']);
            
            $category = Category::create($data);
            
            foreach ($subcategories as $index => $subName) {
                SubCategory::create([
                    'category_id' => $category->id,
                    'name' => $subName,
                    'order' => $index + 1
                ]);
            }
        }
    }
}
