<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Service;
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

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

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
