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
        $shops = Shop::with('colors')->orderBy('created_at', 'desc')->paginate(10);
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
            'colors.*.id' => 'nullable|integer',
            'colors.*.existing_image' => 'nullable|string',
            'colors.*.color_name' => 'required|string|max:255',
            'colors.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $shop->update($validated);

        // Handle colors and their images
        if ($request->has('colors')) {
            $submittedIds = [];
            
            foreach ($request->colors as $colorData) {
                if (isset($colorData['color_name']) && !empty($colorData['color_name'])) {
                    $colorData['shop_id'] = $shop->id;
                    
                    if (isset($colorData['image']) && $colorData['image'] instanceof \Illuminate\Http\UploadedFile) {
                        $imagePath = $colorData['image']->store('shop_colors', 'public');
                        $colorData['image'] = 'storage/' . $imagePath;
                    } elseif (isset($colorData['existing_image'])) {
                        $colorData['image'] = $colorData['existing_image'];
                    } else {
                        $colorData['image'] = null;
                    }
                    
                    $cleanData = collect($colorData)->except(['existing_image'])->toArray();
                    
                    if (isset($colorData['id']) && !empty($colorData['id'])) {
                        $shopColor = ShopColor::where('shop_id', $shop->id)->where('id', $colorData['id'])->first();
                        if ($shopColor) {
                            $shopColor->update($cleanData);
                            $submittedIds[] = $shopColor->id;
                        }
                    } else {
                        $newColor = ShopColor::create($cleanData);
                        $submittedIds[] = $newColor->id;
                    }
                }
            }
            
            // Delete all colors that were not submitted in the edit form
            ShopColor::where('shop_id', $shop->id)->whereNotIn('id', $submittedIds)->delete();
        } else {
            // Delete all colors if the colors input array is not sent at all (meaning all colors were deleted)
            $shop->colors()->delete();
        }

        return redirect()->route('shops.admin.index')->with('success', 'Shop product updated successfully!');
    }

    public function destroy($id)
    {
        $shop = Shop::findOrFail($id);
        $shop->colors()->delete();
        $shop->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('shops.admin.index')->with('success', 'Shop product deleted successfully!');
    }

    public function apiIndex()
    {
        $shops = Shop::with('colors')->orderBy('created_at', 'desc')->get();
        $data = [];
        foreach ($shops as $shop) {
            $imageUrl = null;
            if ($shop->image) {
                $imageUrl = str_starts_with($shop->image, 'http') ? $shop->image : asset($shop->image);
            } elseif ($shop->colors->count() > 0 && $shop->colors->first()->image) {
                $colorImage = $shop->colors->first()->image;
                $imageUrl = str_starts_with($colorImage, 'http') ? $colorImage : asset($colorImage);
            }

            $colorsData = [];
            foreach ($shop->colors as $color) {
                $colorImageUrl = null;
                if ($color->image) {
                    $colorImageUrl = str_starts_with($color->image, 'http') ? $color->image : asset($color->image);
                }
                $colorsData[] = [
                    'id' => $color->id,
                    'color_name' => $color->color_name,
                    'image' => $colorImageUrl,
                ];
            }

            $data[] = [
                'id' => $shop->id,
                'name' => $shop->product_name,
                'price' => '£' . number_format($shop->price, 2),
                'image' => $imageUrl,
                'category' => $shop->category,
                'description' => $shop->description,
                'colors' => $colorsData,
            ];
        }
        return response()->json($data);
    }
    
}
