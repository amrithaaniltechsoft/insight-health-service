<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Return all categories (public API) – includes slug & promo card fields.
     */
    public function getPublicCategories()
    {
        $categories = Category::all();

        $desiredOrder = [
            'pregnancy-scans',
            'diagnostics',
            'msk-scans',
            'musculoskeletal-ultrasound-scan',
            'health-mot',
            'physiotherapy',
            'blood-tests',
            'acupuncture',
            'Acupunctur',
            'joint-injections',
            'servical-screening',
            'cervical-screening'
        ];

        $sorted = $categories->sortBy(function ($category) use ($desiredOrder) {
            if (isset($category->sort_order) && (int)$category->sort_order > 0) {
                return (int)$category->sort_order;
            }
            $slugLower = strtolower($category->slug);
            $index = array_search($slugLower, array_map('strtolower', $desiredOrder));
            return $index !== false ? ($index + 1) : (900 + $category->id);
        });

        $data = $sorted->values()->map(function ($category) {
            return [
                'id'                => $category->id,
                'name'              => $category->name,
                'slug'              => $category->slug,
                'description'       => $category->description,
                'promo_title'       => $category->promo_title,
                'promo_description' => $category->promo_description,
                'promo_link_text'   => $category->promo_link_text,
                'promo_link_href'   => $category->promo_link_href,
                'promo_bg_type'     => $category->promo_bg_type ?? 'pearl',
            ];
        });

        return response()->json($data);
    }

    /**
     * Return subcategories for a category by numeric ID (public API).
     */
    public function getSubCategories($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        return response()->json(
            $category->subCategories()
                ->orderBy('order')
                ->get(['id', 'name', 'order'])
        );
    }

    /**
     * Return subcategories for a category by slug (public API).
     */
    public function getSubCategoriesBySlug(string $slug)
    {
        $category = Category::where('slug', $slug)->first();
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        return response()->json(
            $category->subCategories()
                ->orderBy('order')
                ->get(['id', 'name', 'order'])
        );
    }

    /**
     * Admin: show the categories table (manages slug + promo fields inline).
     */
    public function adminIndex()
    {
        $categories = Category::all();
        $desiredOrder = [
            'pregnancy-scans',
            'diagnostics',
            'msk-scans',
            'musculoskeletal-ultrasound-scan',
            'health-mot',
            'physiotherapy',
            'blood-tests',
            'acupuncture',
            'Acupunctur',
            'joint-injections',
            'servical-screening',
            'cervical-screening'
        ];

        $categories = $categories->sortBy(function ($category) use ($desiredOrder) {
            if (isset($category->sort_order) && (int)$category->sort_order > 0) {
                return (int)$category->sort_order;
            }
            $slugLower = strtolower($category->slug);
            $index = array_search($slugLower, array_map('strtolower', $desiredOrder));
            return $index !== false ? ($index + 1) : (900 + $category->id);
        })->values();

        return view('categories.admin.index', compact('categories'));
    }

    /**
     * Admin AJAX: update slug and promo card fields for a category.
     */
    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'slug'              => 'nullable|string|max:100',
            'promo_title'       => 'nullable|string|max:255',
            'promo_description' => 'nullable|string',
            'promo_link_text'   => 'nullable|string|max:255',
            'promo_link_href'   => 'nullable|string|max:255',
            'promo_bg_type'     => 'nullable|in:pearl,zinc',
        ]);

        // Auto-generate slug from name if empty
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($category->name);
        }

        $category->update($validated);

        return response()->json(['success' => true, 'category' => $category]);
    }
}
