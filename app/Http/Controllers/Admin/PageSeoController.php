<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PageSeoController extends Controller
{
    /**
     * Display a listing of page SEO records.
     */
    public function adminIndex()
    {
        $pageSeos = PageSeo::orderBy('created_at', 'desc')->get();
        return view('page_seos.admin.index', compact('pageSeos'));
    }

    /**
     * Show the form for creating a new page SEO record.
     */
    public function create()
    {
        return view('page_seos.admin.create');
    }

    /**
     * Store a newly created page SEO record in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'page_name' => 'required|string|max:255|unique:page_seos,page_name',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:3072',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|string|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'robots' => 'nullable|string|max:100',
            'schema_markup' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('page_seo', 'public');
            $validated['image'] = 'storage/' . $imagePath;
        }

        PageSeo::create($validated);

        return redirect()->route('page_seos.admin.index')->with('success', 'Page SEO created successfully!');
    }

    /**
     * Show the form for editing the specified page SEO record.
     */
    public function edit($id)
    {
        $pageSeo = PageSeo::findOrFail($id);
        return view('page_seos.admin.edit', compact('pageSeo'));
    }

    /**
     * Get details for preview modal.
     */
    public function show($id)
    {
        $pageSeo = PageSeo::findOrFail($id);
        return response()->json([
            'id' => $pageSeo->id,
            'page_name' => $pageSeo->page_name,
            'title' => $pageSeo->title,
            'description' => $pageSeo->description,
            'image' => $pageSeo->image_url,
            'meta_title' => $pageSeo->meta_title,
            'meta_description' => $pageSeo->meta_description,
            'meta_keywords' => $pageSeo->meta_keywords,
            'canonical_url' => $pageSeo->canonical_url,
            'og_title' => $pageSeo->og_title,
            'og_description' => $pageSeo->og_description,
            'robots' => $pageSeo->robots ?? 'index, follow',
            'schema_markup' => $pageSeo->schema_markup,
            'created_at' => $pageSeo->created_at?->format('d M Y, h:i A'),
            'updated_at' => $pageSeo->updated_at?->format('d M Y, h:i A'),
        ]);
    }

    /**
     * Update the specified page SEO record in storage.
     */
    public function update(Request $request, $id)
    {
        $pageSeo = PageSeo::findOrFail($id);

        $validated = $request->validate([
            'page_name' => 'required|string|max:255|unique:page_seos,page_name,' . $id,
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:3072',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|string|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'robots' => 'nullable|string|max:100',
            'schema_markup' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            if ($pageSeo->image && str_starts_with($pageSeo->image, 'storage/')) {
                $oldPath = str_replace('storage/', '', $pageSeo->image);
                Storage::disk('public')->delete($oldPath);
            }
            $imagePath = $request->file('image')->store('page_seo', 'public');
            $validated['image'] = 'storage/' . $imagePath;
        }

        $pageSeo->update($validated);

        return redirect()->route('page_seos.admin.index')->with('success', 'Page SEO updated successfully!');
    }

    /**
     * Remove the specified page SEO record from storage.
     */
    public function destroy($id)
    {
        $pageSeo = PageSeo::findOrFail($id);

        if ($pageSeo->image && str_starts_with($pageSeo->image, 'storage/')) {
            $oldPath = str_replace('storage/', '', $pageSeo->image);
            Storage::disk('public')->delete($oldPath);
        }

        $pageSeo->delete();

        return redirect()->route('page_seos.admin.index')->with('success', 'Page SEO deleted successfully!');
    }

    /**
     * Public API endpoint to fetch Page SEO data for the frontend.
     */
    public function getPublicPageSeo($pageName)
    {
        $clean = trim($pageName, '/');
        $pageSeo = PageSeo::where('page_name', $clean)
            ->orWhere('page_name', '/' . $clean)
            ->orWhereRaw('LOWER(REPLACE(page_name, " ", "-")) = ?', [strtolower($clean)])
            ->orWhereRaw('LOWER(page_name) = ?', [strtolower($clean)])
            ->orWhere('canonical_url', 'http://localhost:3000/' . $clean)
            ->orWhere('canonical_url', 'https://insighthealthservices.co.uk/' . $clean)
            ->orWhereRaw('canonical_url LIKE ?', ['%/' . $clean])
            ->orWhereRaw('LOWER(page_name) LIKE ?', [strtolower($clean) . '%'])
            ->first();

        if (!$pageSeo) {
            return response()->json(null, 404);
        }

        return response()->json([
            'id' => $pageSeo->id,
            'page_name' => $pageSeo->page_name,
            'title' => $pageSeo->title,
            'description' => $pageSeo->description,
            'image' => $pageSeo->image_url,
            'meta_title' => $pageSeo->meta_title ?: $pageSeo->title,
            'meta_description' => $pageSeo->meta_description ?: $pageSeo->description,
            'meta_keywords' => $pageSeo->meta_keywords,
            'canonical_url' => $pageSeo->canonical_url,
            'robots' => $pageSeo->robots ?? 'index, follow',
            'og_title' => $pageSeo->og_title ?: ($pageSeo->meta_title ?: $pageSeo->title),
            'og_description' => $pageSeo->og_description ?: ($pageSeo->meta_description ?: $pageSeo->description),
            'schema_markup' => $pageSeo->schema_markup,
        ]);
    }
}
