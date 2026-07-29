<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seo;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    public function adminIndex()
    {
        $seos = Seo::orderBy('created_at', 'desc')->paginate(10);
        return view('seos.admin.index', compact('seos'));
    }

    public function getSeos()
    {
        $seos = Seo::orderBy('created_at', 'desc')->get();
        $data = [];
        foreach ($seos as $seo) {
            $data[] = [
                'id' => $seo->id,
                'page' => $seo->page,
                'meta_title' => $seo->meta_title,
                'meta_description' => $seo->meta_description,
                'meta_keywords' => $seo->meta_keywords,
            ];
        }
        return response()->json(['data' => $data]);
    }

    public function getSeoById($id)
    {
        $seo = Seo::findOrFail($id);
        return response()->json([
            'id' => $seo->id,
            'page' => $seo->page,
            'meta_title' => $seo->meta_title,
            'meta_description' => $seo->meta_description,
            'meta_keywords' => $seo->meta_keywords,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'page' => 'required|string|max:255|unique:seos,page',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        Seo::create($validated);

        return redirect()->route('seos.admin.index')->with('success', 'SEO record created successfully!');
    }

    public function update(Request $request, $id)
    {
        $seo = Seo::findOrFail($id);

        $validated = $request->validate([
            'page' => 'required|string|max:255|unique:seos,page,' . $id,
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        $seo->update($validated);

        return redirect()->route('seos.admin.index')->with('success', 'SEO record updated successfully!');
    }

    public function destroy($id)
    {
        $seo = Seo::findOrFail($id);
        $seo->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('seos.admin.index')->with('success', 'SEO record deleted successfully!');
    }

    public function getPublicSeoByPage($page)
    {
        $seo = Seo::whereRaw('LOWER(page) = ?', [strtolower($page)])->first();
        if (!$seo) {
            return response()->json(null, 404);
        }
        return response()->json([
            'meta_title' => $seo->meta_title,
            'meta_description' => $seo->meta_description,
            'meta_keywords' => $seo->meta_keywords,
        ]);
    }
}
