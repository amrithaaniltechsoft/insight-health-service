<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
     * Slugify the slug portion typed by the admin and build the full canonical URL.
     * Existing records that already have a full URL stored are NOT touched (data-safe).
     */
    private function buildCanonicalUrl(?string $slugInput): ?string
    {
        if (!$slugInput) return null;

        $base = rtrim(env('FRONTEND_URL', 'https://insighthealthservices.co.uk'), '/');

        // If the admin already typed a full URL (starts with http), just slugify the path portion
        if (str_starts_with($slugInput, 'http://') || str_starts_with($slugInput, 'https://')) {
            // Extract path, slugify each segment, rebuild
            $parsed = parse_url($slugInput);
            $path   = trim($parsed['path'] ?? '', '/');
            $slug   = collect(explode('/', $path))
                ->map(fn($seg) => Str::slug($seg))
                ->filter()
                ->implode('/');
            return $base . '/' . $slug;
        }

        // Plain slug / path typed by admin
        $slug = collect(explode('/', trim($slugInput, '/')))
            ->map(fn($seg) => Str::slug($seg))
            ->filter()
            ->implode('/');

        return $slug ? $base . '/' . $slug : null;
    }

    /**
     * Extract the slug portion from a full canonical URL or plain slug input.
     */
    private function extractSlug(?string $canonicalOrSlug): ?string
    {
        if (!$canonicalOrSlug) return null;
        $base = rtrim(env('FRONTEND_URL', 'https://insighthealthservices.co.uk'), '/');
        // Strip the base URL prefix if present
        if (str_starts_with($canonicalOrSlug, $base . '/')) {
            return substr($canonicalOrSlug, strlen($base) + 1);
        }
        return ltrim($canonicalOrSlug, '/');
    }

    /**
     * Clean and normalize rich HTML content (decodes entities if pasted as raw markup, strips Word artifacts).
     */
    private function cleanDescription(?string $content): ?string
    {
        if (!$content) return null;

        $html = $content;

        // If content was saved as escaped entities (&lt;p...&gt;), decode into real HTML
        if (preg_match('/&(?:lt|#60|#x3c);(p|div|span|h[1-6]|ul|ol|li|table|a|b|strong|i|em|br)\b/i', $html)) {
            $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Clean up Microsoft Word / Office artifacts like <o:p></o:p>
        $html = preg_replace('/<o:p\b[^>]*>.*?<\/o:p>/is', '', $html);
        $html = preg_replace('/<\/?o:p[^>]*>/i', '', $html);

        return $html;
    }

    /**
     * Store a newly created page SEO record in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'page_name'       => 'required|string|max:255|unique:page_seos,page_name',
            'title'           => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:3072',
            'meta_title'      => 'nullable|string|max:255',
            'meta_description'=> 'nullable|string',
            'meta_keywords'   => 'nullable|string|max:500',
            'canonical_url'   => 'nullable|string|max:500',
            'og_title'        => 'nullable|string|max:255',
            'og_description'  => 'nullable|string',
            'robots'          => 'nullable|string|max:100',
            'schema_markup'   => 'nullable|string',
        ]);

        // Slugify the canonical URL input
        if (!empty($validated['canonical_url'])) {
            $validated['canonical_url'] = $this->buildCanonicalUrl($validated['canonical_url']);
        }

        if (array_key_exists('description', $validated)) {
            $validated['description'] = $this->cleanDescription($validated['description']);
        }

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
        $base = rtrim(env('FRONTEND_URL', 'https://insighthealthservices.co.uk'), '/');
        $rawCanonical = $pageSeo->canonical_url;
        $fullCanonical = $rawCanonical
            ? (str_starts_with($rawCanonical, 'http') ? $rawCanonical : $base . '/' . ltrim($rawCanonical, '/'))
            : null;

        return response()->json([
            'id' => $pageSeo->id,
            'page_name' => $pageSeo->page_name,
            'title' => $pageSeo->title,
            'description' => $this->cleanDescription($pageSeo->description),
            'image' => $pageSeo->image_url,
            'meta_title' => $pageSeo->meta_title,
            'meta_description' => $pageSeo->meta_description,
            'meta_keywords' => $pageSeo->meta_keywords,
            'canonical_url' => $fullCanonical,
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

        // Slugify the canonical URL input
        if (!empty($validated['canonical_url'])) {
            $validated['canonical_url'] = $this->buildCanonicalUrl($validated['canonical_url']);
        }

        if (array_key_exists('description', $validated)) {
            $validated['description'] = $this->cleanDescription($validated['description']);
        }

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
     * Primary lookup: canonical_url (supports full URLs with domain, or slug-only values).
     * Fallback: page_name matching (backwards-compatible with existing data).
     */
    public function getPublicPageSeo($pageName)
    {
        $clean    = strtolower(trim($pageName, '/'));
        $base     = rtrim(env('FRONTEND_URL', 'https://insighthealthservices.co.uk'), '/');
        $fullUrl  = $base . '/' . $clean;

        $pageSeo = PageSeo::where(function($q) use ($clean, $fullUrl, $base) {
            $q->where('canonical_url', $clean)
              ->orWhere('canonical_url', '/' . $clean)
              ->orWhere('canonical_url', $clean . '/')
              ->orWhere('canonical_url', '/' . $clean . '/')
              ->orWhere('canonical_url', $fullUrl)
              ->orWhere('canonical_url', $fullUrl . '/')
              ->orWhereRaw("LOWER(TRIM(BOTH '/' FROM canonical_url)) = ?", [$clean])
              ->orWhereRaw("LOWER(TRIM(BOTH '/' FROM REPLACE(canonical_url, ?, ''))) = ?", [$base, $clean])
              ->orWhereRaw("LOWER(TRIM(BOTH '/' FROM REPLACE(canonical_url, 'https://insighthealthservices.co.uk', ''))) = ?", [$clean])
              ->orWhereRaw("LOWER(TRIM(BOTH '/' FROM REPLACE(canonical_url, 'http://localhost:3000', ''))) = ?", [$clean])
              ->orWhereRaw("LOWER(TRIM(BOTH '/' FROM REPLACE(canonical_url, 'http://localhost:3001', ''))) = ?", [$clean])
              ->orWhereRaw("LOWER(TRIM(BOTH '/' FROM REPLACE(canonical_url, 'http://127.0.0.1:8000', ''))) = ?", [$clean])
              ->orWhereRaw("LOWER(canonical_url) LIKE ?", ['%/' . $clean])
              ->orWhereRaw("LOWER(canonical_url) LIKE ?", ['%/' . $clean . '/'])
              ->orWhere('page_name', $clean)
              ->orWhere('page_name', '/' . $clean)
              ->orWhereRaw('LOWER(REPLACE(page_name, " ", "-")) = ?', [$clean])
              ->orWhereRaw('LOWER(page_name) = ?', [$clean])
              ->orWhereRaw('LOWER(page_name) LIKE ?', [$clean . '%']);
        })->first();

        if (!$pageSeo) {
            return response()->json(null, 404);
        }

        $cleanMetaDesc = $pageSeo->meta_description 
            ? strip_tags($pageSeo->meta_description) 
            : ($pageSeo->description ? trim(preg_replace('/\s+/', ' ', strip_tags($pageSeo->description))) : null);

        $cleanOgDesc = $pageSeo->og_description 
            ? strip_tags($pageSeo->og_description) 
            : $cleanMetaDesc;

        $returnCanonical = $pageSeo->canonical_url;
        if ($returnCanonical) {
            if (!str_starts_with($returnCanonical, 'http://') && !str_starts_with($returnCanonical, 'https://')) {
                $returnCanonical = $base . '/' . ltrim($returnCanonical, '/');
            }
        } else {
            $returnCanonical = $base . '/' . $clean;
        }

        return response()->json([
            'id' => $pageSeo->id,
            'page_name' => $pageSeo->page_name,
            'title' => $pageSeo->title,
            'description' => $this->cleanDescription($pageSeo->description),
            'image' => $pageSeo->image_url,
            'meta_title' => $pageSeo->meta_title ?: $pageSeo->title,
            'meta_description' => $cleanMetaDesc,
            'meta_keywords' => $pageSeo->meta_keywords,
            'canonical_url' => $returnCanonical,
            'robots' => $pageSeo->robots ?? 'index, follow',
            'og_title' => $pageSeo->og_title ?: ($pageSeo->meta_title ?: $pageSeo->title),
            'og_description' => $cleanOgDesc,
            'schema_markup' => $pageSeo->schema_markup,
        ]);
    }
}
