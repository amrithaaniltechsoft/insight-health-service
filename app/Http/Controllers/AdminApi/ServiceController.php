<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Category;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with(['category', 'subCategory'])->get()->map(function($s) {
            $name = !empty($s->title) ? $s->title : (!empty($s->service_name) ? $s->service_name : 'Health Service');
            $categoryName = $s->category ? $s->category->name : 'General';
            $duration = !empty($s->appointment) ? $s->appointment : '30 Min';
            $description = !empty($s->service_overview) ? $s->service_overview : (!empty($s->description1) ? $s->description1 : '');

            return [
                'id' => $s->id,
                'title' => $name,
                'name' => $name,
                'category' => $categoryName,
                'subCategory' => $s->subCategory ? $s->subCategory->name : null,
                'price' => (float) $s->price,
                'offerPrice' => (float) $s->price,
                'duration' => $duration,
                'description' => strip_tags($description),
                'rawDescription' => $description,
                'status' => 'active',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $services
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'price' => 'nullable|numeric',
            'duration' => 'nullable|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
        ]);

        $categoryId = 1;
        if ($request->has('category') && !empty($request->category)) {
            $cat = Category::where('name', $request->category)->first();
            if ($cat) {
                $categoryId = $cat->id;
            }
        }

        $service = Service::create([
            'title' => $request->title,
            'service_name' => $request->title,
            'price' => $request->price ?? 0,
            'appointment' => $request->duration ?? '30 Min',
            'service_overview' => $request->description ?? '',
            'category_id' => $categoryId,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Service created successfully',
            'data' => $service
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $rawId = intval(preg_replace('/[^0-9]/', '', $id));

        $service = Service::where('id', $id)
            ->orWhere('id', $rawId)
            ->first();

        if (!$service) {
            // Auto-create service record if new client-generated ID is updated
            $categoryId = 1;
            if ($request->has('category') && !empty($request->category)) {
                $cat = Category::where('name', $request->category)->first();
                if ($cat) $categoryId = $cat->id;
            }

            $service = Service::create([
                'title' => $request->input('title', 'New Service'),
                'service_name' => $request->input('title', 'New Service'),
                'price' => $request->input('price', 0),
                'appointment' => $request->input('duration', '30 Min'),
                'service_overview' => $request->input('description', ''),
                'category_id' => $categoryId,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Service created successfully',
                'data' => $service
            ], 201);
        }

        $data = [];
        if ($request->has('title') && !empty($request->title)) {
            $data['title'] = $request->title;
            $data['service_name'] = $request->title;
        }
        if ($request->has('price')) {
            $data['price'] = $request->price;
        }
        if ($request->has('duration')) {
            $data['appointment'] = $request->duration;
        }
        if ($request->has('description')) {
            $data['service_overview'] = $request->description;
        }
        if ($request->has('category') && !empty($request->category)) {
            $cat = Category::where('name', $request->category)->first();
            if ($cat) {
                $data['category_id'] = $cat->id;
            }
        }

        if (!empty($data)) {
            $service->update($data);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Service catalog updated successfully',
            'data' => $service
        ]);
    }
}
