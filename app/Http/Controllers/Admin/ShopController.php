<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function adminIndex()
    {
        $shops = Shop::orderBy('created_at', 'desc')->paginate(10);
        $categories = ['Teddies with Heartbeat Monitor', 'Gender Reveal Options'];
        return view('shops.admin.index', compact('shops', 'categories'));
    }

    public function getShops()
    {
        $shops = Shop::orderBy('created_at', 'desc')->get();
        $data = [];
        foreach ($shops as $shop) {
            $imageUrl = null;
            if ($shop->image) {
                $imageUrl = str_starts_with($shop->image, 'http') ? $shop->image : asset($shop->image);
            }
            $data[] = [
                'id' => $shop->id,
                'category' => $shop->category,
                'product_name' => $shop->product_name,
                'price' => number_format($shop->price, 2),
                'image' => $imageUrl,
            ];
        }
        return response()->json(['data' => $data]);
    }

    public function getShopById($id)
    {
        $shop = Shop::findOrFail($id);
        $imageUrl = null;
        if ($shop->image) {
            if (str_starts_with($shop->image, 'http')) {
                $filename = basename(parse_url($shop->image, PHP_URL_PATH));
                $imageUrl = asset('storage/shops/' . $filename);
            } else {
                $imageUrl = asset($shop->image);
            }
        }
        return response()->json([
            'id' => $shop->id,
            'category' => $shop->category,
            'product_name' => $shop->product_name,
            'price' => $shop->price,
            'description' => $shop->description,
            'image' => $imageUrl,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'product_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('shops', 'public');
            $validated['image'] = 'storage/' . $imagePath;
        }

        Shop::create($validated);
        return redirect()->route('shops.admin.index')->with('success', 'Shop product created successfully!');
    }

    public function update(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $validated = $request->validate([
            'category' => 'required|string',
            'product_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('shops', 'public');
            $validated['image'] = 'storage/' . $imagePath;
        }

        $shop->update($validated);
        return redirect()->route('shops.admin.index')->with('success', 'Shop product updated successfully!');
    }

    public function destroy($id)
    {
        $shop = Shop::findOrFail($id);
        $shop->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('shops.admin.index')->with('success', 'Shop product deleted successfully!');
    }

    public function apiIndex()
    {
        $shops = Shop::orderBy('created_at', 'desc')->get();
        $data = [];
        foreach ($shops as $shop) {
            $imageUrl = null;
            if ($shop->image) {
                $imageUrl = str_starts_with($shop->image, 'http') ? $shop->image : asset($shop->image);
            }
            $data[] = [
                'id' => $shop->id,
                'name' => $shop->product_name,
                'price' => '£' . number_format($shop->price, 2),
                'image' => $imageUrl,
                'category' => $shop->category,
                'description' => $shop->description,
            ];
        }
        return response()->json($data);
    }
    
}
