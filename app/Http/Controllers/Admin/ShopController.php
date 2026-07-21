<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopColor;
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
            'colors' => 'nullable|array',
            'colors.*.color_name' => 'required|string|max:255',
            'colors.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $shop = Shop::create($validated);

        // Handle colors and their images
        if ($request->has('colors')) {
            foreach ($request->colors as $colorData) {
                if (isset($colorData['color_name']) && !empty($colorData['color_name'])) {
                    $colorData['shop_id'] = $shop->id;
                    
                    if (isset($colorData['image']) && $colorData['image'] instanceof \Illuminate\Http\UploadedFile) {
                        $imagePath = $colorData['image']->store('shop_colors', 'public');
                        $colorData['image'] = 'storage/' . $imagePath;
                    }
                    
                    ShopColor::create($colorData);
                }
            }
        }

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
            'colors' => 'nullable|array',
            'colors.*.color_name' => 'required|string|max:255',
            'colors.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $shop->update($validated);

        // Handle colors and their images
        if ($request->has('colors')) {
            // Delete existing colors
            $shop->colors()->delete();
            
            foreach ($request->colors as $colorData) {
                if (isset($colorData['color_name']) && !empty($colorData['color_name'])) {
                    $colorData['shop_id'] = $shop->id;
                    
                    if (isset($colorData['image']) && $colorData['image'] instanceof \Illuminate\Http\UploadedFile) {
                        $imagePath = $colorData['image']->store('shop_colors', 'public');
                        $colorData['image'] = 'storage/' . $imagePath;
                    }
                    
                    ShopColor::create($colorData);
                }
            }
        }

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
