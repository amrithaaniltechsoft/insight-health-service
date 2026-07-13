<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Support\ServiceProvider;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app['events']->listen(BuildingMenu::class, function (BuildingMenu $event) {
            $iconMap = [
                1 => 'fas fa-fw fa-baby',
                2 => 'fas fa-fw fa-stethoscope',
                3 => 'fas fa-fw fa-dumbbell',
                4 => 'fas fa-fw fa-tint',
                5 => 'fas fa-fw fa-flask',
            ];

            $categories = Category::orderBy('id')->get();

            foreach ($categories as $category) {
                $event->menu->addIn('services', [
                    'text' => $category->name,
                    'url' => route('services.admin.category', $category->id),
                    'icon' => $iconMap[$category->id] ?? 'fas fa-fw fa-concierge-bell',
                ]);
            }

            $event->menu->addIn('services', [
                'text' => 'All Services',
                'url' => 'admin/services',
                'icon' => 'fas fa-fw fa-list',
            ]);
        });
    }
}
