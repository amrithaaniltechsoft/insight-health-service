<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Service;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ServiceController extends Controller
{
    public function adminIndex()
    {
        $categories = Category::withCount('services')->orderBy('id')->get();
        return view('services.admin.index', compact('categories'));
    }

    /**
     * Public API: get all services for a category by its slug.
     * Returns structured data ready for the Next.js frontend.
     */
    public function getPublicServicesBySlug(string $slug)
    {
        $category = Category::where('slug', $slug)->first();

        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        $services = Service::where('category_id', $category->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $API_BASE = config('app.url');

        $data = $services->map(function ($service) use ($API_BASE, $slug) {
            $imageUrl = null;
            if ($service->image) {
                $imageUrl = str_starts_with($service->image, 'http')
                    ? $service->image
                    : $API_BASE . '/' . $service->image;
            }

            $slugSource = $service->title ?? $service->service_name;
            $serviceSlug = \Illuminate\Support\Str::slug($slugSource);

            return [
                'id'               => $service->id,
                'slug'             => $serviceSlug,
                'title'            => $service->title ?? $service->service_name,
                'service_name'     => $service->service_name,
                'service_overview' => $service->service_overview,
                'price'            => $service->price ? '£' . number_format($service->price, 0) : null,
                'appointment'      => $service->appointment,
                'description1'     => $service->description1,
                'description2'     => $service->description2,
                'package_include'  => $service->package_include,
                'turn_around_time' => $service->turn_around_time,
                'video_link'       => $service->video_link,
                'faq_link'         => $service->faq_link,
                'image'            => $imageUrl,
                'category_slug'    => $slug,
            ];
        });

        return response()->json([
            'category' => [
                'id'          => $category->id,
                'name'        => $category->name,
                'slug'        => $category->slug,
                'description' => $category->description,
            ],
            'services' => $data,
        ]);
    }

    /**
     * Public API: get a single service by category slug + service slug.
     */
    public function getPublicServiceBySlug(string $categorySlug, string $serviceSlug)
    {
        $category = Category::where('slug', $categorySlug)->first();
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        // Normalize: collapse multiple dashes like Str::slug does
        $normalizedSlug = preg_replace('/-+/', '-', $serviceSlug);

        // Match by slugified service_name, title, or slug
        $services = Service::where('category_id', $category->id)->get();
        $service = $services->first(function ($s) use ($normalizedSlug) {
            $slugSource1 = \Illuminate\Support\Str::slug($s->title ?? '');
            $slugSource2 = \Illuminate\Support\Str::slug($s->service_name ?? '');

            // Exact match first (highest priority)
            if ($slugSource1 === $normalizedSlug || $slugSource2 === $normalizedSlug) {
                return true;
            }

            // Fuzzy match — only if slugs are non-empty
            if ($slugSource1 !== '' && $slugSource2 !== '') {
                return str_contains($slugSource1, $normalizedSlug) 
                    || str_contains($normalizedSlug, $slugSource1)
                    || str_contains($slugSource2, $normalizedSlug)
                    || str_contains($normalizedSlug, $slugSource2);
            }

            return false;
        });

        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        $API_BASE = config('app.url');
        $imageUrl = null;
        if ($service->image) {
            $imageUrl = str_starts_with($service->image, 'http')
                ? $service->image
                : $API_BASE . '/' . $service->image;
        }

        // Parse package_include into inclusions array (strip HTML, split by line)
        $inclusions = [];
        if ($service->package_include) {
            $inclusions = collect(
                preg_split('/\r?\n|<br\s*\/?>/i',
                    strip_tags($service->package_include)
                )
            )->map(fn($l) => trim($l))->filter()->values()->toArray();
        }

        // Fetch FAQs specifically assigned to this service's subcategory
        $subCatId = $service->sub_category_id;
        $subCatName = $service->subCategory?->name ?? $service->service_name ?? $service->title;

        // Find matching subcategories by ID or name
        $matchingSubCategoryIds = \App\Models\SubCategory::where('category_id', $category->id)
            ->where(function ($q) use ($subCatId, $subCatName, $service) {
                if ($subCatId) {
                    $q->orWhere('id', $subCatId);
                }
                if ($subCatName) {
                    $q->orWhere('name', 'LIKE', '%' . trim($subCatName) . '%');
                }
                if ($service->service_name) {
                    $q->orWhere('name', 'LIKE', '%' . trim($service->service_name) . '%');
                }
                if ($service->title) {
                    $q->orWhere('name', 'LIKE', '%' . trim($service->title) . '%');
                }
            })
            ->pluck('id')
            ->toArray();

        // Also check Faq subcategories whose name matches the service/subcategory name
        $faqSubCatIds = \App\Models\Faq::where('category_id', $category->id)
            ->whereNotNull('sub_category_id')
            ->pluck('sub_category_id')
            ->unique();

        $matchingFaqSubCatIds = \App\Models\SubCategory::whereIn('id', $faqSubCatIds)
            ->get()
            ->filter(function ($sc) use ($subCatName, $service) {
                $scName = strtolower(trim($sc->name));
                $sName = strtolower(trim($service->service_name ?? ''));
                $tName = strtolower(trim($service->title ?? ''));
                $targetName = strtolower(trim($subCatName ?? ''));

                return $scName !== '' && (
                    str_contains($sName, $scName) || str_contains($scName, $sName) ||
                    str_contains($tName, $scName) || str_contains($scName, $tName) ||
                    str_contains($targetName, $scName) || str_contains($scName, $targetName)
                );
            })
            ->pluck('id')
            ->toArray();

        $allSubCatIds = array_unique(array_merge($matchingSubCategoryIds, $matchingFaqSubCatIds));

        $faqs = collect();
        if (!empty($allSubCatIds)) {
            $faqs = \App\Models\Faq::where('category_id', $category->id)
                ->whereIn('sub_category_id', $allSubCatIds)
                ->orderBy('created_at', 'asc')
                ->get(['question', 'answer'])
                ->map(fn($f) => ['q' => $f->question, 'a' => $f->answer]);
        }

        return response()->json([
            'service' => [
                'id'               => $service->id,
                'slug'             => \Illuminate\Support\Str::slug($service->service_name),
                'title'            => $service->title ?? $service->service_name,
                'service_name'     => $service->service_name,
                'service_overview' => $service->service_overview,
                'price'            => $service->price ? '£' . number_format($service->price, 0) : null,
                'appointment'      => $service->appointment,
                'description1'     => $service->description1,
                'description2'     => $service->description2,
                'package_include'  => $service->package_include,
                'inclusions'       => $inclusions,
                'turn_around_time' => $service->turn_around_time,
                'video_link'       => $service->video_link,
                'faq_link'         => $service->faq_link,
                'preparation'      => $service->preparation,
                'image'            => $imageUrl,
                'category_slug'    => $categorySlug,
            ],
            'category' => [
                'id'               => $category->id,
                'name'             => $category->name,
                'slug'             => $category->slug,
                'description'      => $category->description,
            ],
            'faqs' => $faqs,
        ]);
    }

    public function getServices()
    {
        $categories = Category::withCount('services')->orderBy('id')->get();
        $data = [];
        foreach ($categories as $cat) {
            $data[] = [
                'id' => $cat->id,
                'category' => $cat->name,
                'services_count' => $cat->services_count,
            ];
        }
        return response()->json(['data' => $data]);
    }

    public function categoryServices($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $subCategories = SubCategory::where('category_id', $categoryId)->orderBy('order')->get();
        return view('services.admin.category', compact('category', 'subCategories'));
    }

    public function getCategoryServices($categoryId)
    {
        $services = Service::with('subCategory')->where('category_id', $categoryId)->orderBy('created_at', 'desc')->get();
        $data = [];
        foreach ($services as $service) {
            $imageHtml = '-';
            if ($service->image) {
                $src = str_starts_with($service->image, 'http') ? $service->image : asset($service->image);
                $imageHtml = '<img src="' . $src . '" alt="' . e($service->service_name) . '" style="width:50px;height:50px;object-fit:cover;border-radius:4px;">';
            }
            $data[] = [
                'id' => $service->id,
                'title' => $service->title ?? '-',
                'subcategory' => $service->subCategory ? $service->subCategory->name : ($service->service_name ?? '-'),
                'display_title' => $service->title ?? $service->service_name,
                'price' => $service->price ? number_format($service->price, 2) : '-',
                'image_html' => $imageHtml,
                'category_id' => $service->category_id,
            ];
        }
        return response()->json(['data' => $data]);
    }

    public function getServiceById($id)
    {
        $service = Service::with('category')->findOrFail($id);
        $imageUrl = null;
        if ($service->image) {
            $imageUrl = str_starts_with($service->image, 'http') ? $service->image : asset($service->image);
        }
        return response()->json([
            'id' => $service->id,
            'category_id' => $service->category_id,
            'service_name' => $service->service_name,
            'title' => $service->title,
            'service_overview' => $service->service_overview,
            'price' => $service->price,
            'appointment' => $service->appointment,
            'faq_link' => $service->faq_link,
            'description1' => $service->description1,
            'description2' => $service->description2,
            'package_include' => $service->package_include,
            'turn_around_time' => $service->turn_around_time,
            'video_link' => $service->video_link,
            'image' => $imageUrl,
            'sub_category_id' => $service->sub_category_id,
            'preparation' => $service->preparation,
            'category' => $service->category?->name ?? 'N/A',
        ]);
    }

    public function updateSubCategory(Request $request, $id)
    {
        $subCategory = SubCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $subCategory->update(['name' => $validated['name']]);

        // Update related services' service_name
        Service::where('sub_category_id', $id)->update(['service_name' => $validated['name']]);

        return response()->json(['success' => true, 'name' => $subCategory->name]);
    }

    public function destroySubCategory($id)
    {
        $subCategory = SubCategory::findOrFail($id);

        // Delete related services first
        Service::where('sub_category_id', $id)->delete();

        $subCategory->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Subcategory deleted successfully!');
    }

    public function storeSubCategory(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
        ]);

        $subCategory = SubCategory::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'order' => 0,
        ]);

        return response()->json([
            'success' => true,
            'id' => $subCategory->id,
            'name' => $subCategory->name,
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'category_id' => 'required|exists:categories,id',
            'service_name' => 'nullable|string|max:255',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'title' => 'nullable|string|max:255',
            'faq_link' => 'nullable|string|max:255',
            'description1' => 'nullable|string',
            'description2' => 'nullable|string',
            'package_include' => 'nullable|string',
            'turn_around_time' => 'nullable|string|max:255',
            'video_link' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'preparation' => 'nullable|string',
        ];

        if ($request->category_id == 4) {
            $rules['price'] = 'required|numeric|min:0';
            $rules['title'] = 'required|string|max:255';
            $rules['turn_around_time'] = 'required|string|max:255';
            $rules['description1'] = 'required|string';
            $rules['package_include'] = 'nullable|string';
            $rules['appointment'] = 'nullable|string|max:255';
            $rules['service_overview'] = 'nullable|string';
        } else {
            $rules['price'] = 'required|numeric|min:0';
            $rules['appointment'] = 'required|string|max:255';
            $rules['service_overview'] = 'required|string';
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('services', 'public');
            $validated['image'] = 'storage/' . $imagePath;
        }

        if ($request->filled('sub_category_id')) {
            $subCategory = SubCategory::findOrFail($request->sub_category_id);
            $validated['sub_category_id'] = $subCategory->id;
            if ($validated['category_id'] == 4) {
                $validated['service_name'] = $subCategory->name;
            } else {
                if (empty($validated['title'])) {
                    $validated['title'] = $validated['service_name'];
                }
            }
        } else {
            $subCategory = SubCategory::create([
                'category_id' => $validated['category_id'],
                'name' => $validated['service_name'] ?? 'General',
                'order' => 0,
            ]);
            $validated['sub_category_id'] = $subCategory->id;
            if (empty($validated['title'])) {
                $validated['title'] = $validated['service_name'] ?? 'General';
            }
        }

        Service::create($validated);
        return redirect()->back()->with('success', 'Service created successfully!');
    }

    public function importExcel(Request $request, $categoryId)
    {
        $category = Category::findOrFail($categoryId);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        // Map header row
        $header = array_shift($rows);
        $header = array_map(function ($h) {
            return strtolower(trim(str_replace(' ', '', $h)));
        }, $header);

        $colMap = array_flip($header);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        $sanitizePrice = function ($val) {
            $cleaned = preg_replace('/[^0-9.]/', '', $val ?? '');
            return $cleaned === '' ? null : $cleaned;
        };

        foreach ($rows as $i => $row) {
            $row = array_map('trim', $row);
            $rowIndex = $i + 2;

            try {
                if ($categoryId == 4) {
                    // Blood Tests: Category, Sub-Category, Title, Rate, Description, Package Include, Turn Around Time
                    $subCategoryName = $row[$colMap['sub-category'] ?? $colMap['subcategory'] ?? -1] ?? '';
                    $title = $row[$colMap['title'] ?? -1] ?? '';
                    if (empty($subCategoryName)) {
                        $subCategoryName = !empty($title) ? $title : 'General';
                    }

                    $subCategory = SubCategory::firstOrCreate(
                        ['category_id' => $categoryId, 'name' => $subCategoryName],
                        ['order' => 0]
                    );

                    $title = empty($title) ? $subCategoryName : $title;

                    // Duplicate check: same category + sub_category + title
                    $exists = Service::where('category_id', $categoryId)
                        ->where('sub_category_id', $subCategory->id)
                        ->where('title', $title)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    Service::create([
                        'category_id' => $categoryId,
                        'sub_category_id' => $subCategory->id,
                        'service_name' => $subCategoryName,
                        'title' => $title,
                        'price' => $sanitizePrice($row[$colMap['rate'] ?? -1] ?? null),
                        'turn_around_time' => $row[$colMap['turnaroundtime'] ?? $colMap['turn_around_time'] ?? -1] ?? null,
                        'description1' => $row[$colMap['description'] ?? -1] ?? null,
                        'package_include' => $row[$colMap['packageinclude'] ?? $colMap['package_include'] ?? -1] ?? null,
                    ]);
                } else {
                    // Other categories: header-based mapping
                    $serviceName = $row[$colMap['servicename'] ?? $colMap['service_name'] ?? $colMap['title'] ?? -1] ?? '';
                    if (empty($serviceName)) {
                        $errors[] = "Row $rowIndex: Service name is required.";
                        continue;
                    }

                    $subCategory = SubCategory::firstOrCreate(
                        ['category_id' => $categoryId, 'name' => $serviceName],
                        ['order' => 0]
                    );

                    // Duplicate check
                    $exists = Service::where('category_id', $categoryId)
                        ->where('sub_category_id', $subCategory->id)
                        ->where('service_name', $serviceName)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    Service::create([
                        'category_id' => $categoryId,
                        'sub_category_id' => $subCategory->id,
                        'service_name' => $serviceName,
                        'title' => null,
                        'price' => $sanitizePrice($row[$colMap['price'] ?? -1] ?? null),
                        'appointment' => $row[$colMap['appointment'] ?? -1] ?? null,
                        'service_overview' => $row[$colMap['serviceoverview'] ?? $colMap['service_overview'] ?? -1] ?? null,
                        'faq_link' => $row[$colMap['faqlink'] ?? $colMap['faq_link'] ?? -1] ?? null,
                        'video_link' => $row[$colMap['videolink'] ?? $colMap['video_link'] ?? -1] ?? null,
                        'description1' => $row[$colMap['description'] ?? -1] ?? null,
                        'description2' => $row[$colMap['description2'] ?? $colMap['description_2'] ?? -1] ?? null,
                    ]);
                }
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row $rowIndex: " . $e->getMessage();
            }
        }

        $message = "$imported service(s) imported.";
        if ($skipped > 0) {
            $message .= " $skipped duplicate(s) skipped.";
        }
        if (!empty($errors)) {
            $message .= ' Errors: ' . implode(' | ', $errors);
        }
        return redirect()->back()->with('success', $message);
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $rules = [
            'category_id' => 'required|exists:categories,id',
            'service_name' => 'nullable|string|max:255',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'title' => 'nullable|string|max:255',
            'faq_link' => 'nullable|string|max:255',
            'description1' => 'nullable|string',
            'description2' => 'nullable|string',
            'package_include' => 'nullable|string',
            'turn_around_time' => 'nullable|string|max:255',
            'video_link' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'preparation' => 'nullable|string',
        ];

        if ($request->category_id == 4) {
            $rules['price'] = 'required|numeric|min:0';
            $rules['title'] = 'required|string|max:255';
            $rules['turn_around_time'] = 'required|string|max:255';
            $rules['description1'] = 'required|string';
            $rules['package_include'] = 'nullable|string';
            $rules['appointment'] = 'nullable|string|max:255';
            $rules['service_overview'] = 'nullable|string';
        } else {
            $rules['price'] = 'required|numeric|min:0';
            $rules['appointment'] = 'required|string|max:255';
            $rules['service_overview'] = 'required|string';
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('services', 'public');
            $validated['image'] = 'storage/' . $imagePath;
        }

        if ($request->filled('sub_category_id')) {
            $subCategory = SubCategory::findOrFail($request->sub_category_id);
            $validated['sub_category_id'] = $subCategory->id;
            if ($validated['category_id'] == 4) {
                $validated['service_name'] = $subCategory->name;
            } else {
                if (empty($validated['title'])) {
                    $validated['title'] = $validated['service_name'];
                }
            }
        } else {
            $subCategory = $service->sub_category_id
                ? SubCategory::find($service->sub_category_id)
                : SubCategory::where('category_id', $service->category_id)->where('name', $service->service_name)->first();

            if ($subCategory) {
                $subCategory->update([
                    'category_id' => $validated['category_id'],
                    'name' => $validated['service_name'],
                ]);
                $validated['sub_category_id'] = $subCategory->id;
            } else {
                $subCategory = SubCategory::create([
                    'category_id' => $validated['category_id'],
                    'name' => $validated['service_name'],
                    'order' => 0,
                ]);
                $validated['sub_category_id'] = $subCategory->id;
            }
            if (empty($validated['title'])) {
                $validated['title'] = $validated['service_name'];
            }
        }

        $service->update($validated);
        return redirect()->back()->with('success', 'Service updated successfully!');
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Service deleted successfully!');
    }
}
