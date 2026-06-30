<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms;
use Illuminate\Http\Request;

class CmsController extends Controller
{
    public function adminIndex()
    {
        $cms = Cms::orderBy('created_at', 'desc')->paginate(10);
        return view('cms.admin.index', compact('cms'));
    }

    public function getCms()
    {
        $cms = Cms::orderBy('created_at', 'desc')->get();
        $data = [];
        foreach ($cms as $item) {
            $imageUrl = null;
            if ($item->image) {
                $imageUrl = str_starts_with($item->image, 'http') ? $item->image : asset($item->image);
            }
            $data[] = [
                'id' => $item->id,
                'page' => $item->page,
                'title' => $item->title,
                'image' => $imageUrl,
            ];
        }
        return response()->json(['data' => $data]);
    }

    public function getCmsById($id)
    {
        $item = Cms::findOrFail($id);
        $imageUrl = null;
        if ($item->image) {
            if (str_starts_with($item->image, 'http')) {
                $filename = basename(parse_url($item->image, PHP_URL_PATH));
                $imageUrl = asset('storage/cms/' . $filename);
            } else {
                $imageUrl = asset($item->image);
            }
        }
        return response()->json([
            'id' => $item->id,
            'page' => $item->page,
            'title' => $item->title,
            'description' => $item->description,
            'image' => $imageUrl,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'page' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('cms', 'public');
            $validated['image'] = 'storage/' . $imagePath;
        }

        Cms::create($validated);
        return redirect()->route('cms.admin.index')->with('success', 'CMS page created successfully!');
    }

    public function update(Request $request, $id)
    {
        $item = Cms::findOrFail($id);

        $validated = $request->validate([
            'page' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('cms', 'public');
            $validated['image'] = 'storage/' . $imagePath;
        }

        $item->update($validated);
        return redirect()->route('cms.admin.index')->with('success', 'CMS page updated successfully!');
    }

    public function destroy($id)
    {
        $item = Cms::findOrFail($id);
        $item->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('cms.admin.index')->with('success', 'CMS page deleted successfully!');
    }
}
