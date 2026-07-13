<?php
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json([
        'message' => 'Laravel API is working!'
    ]);
});

Route::get('/services', function () {
    return response()->json([
        [
            "id" => 1,
            "title" => "Blood Test"
        ],
        [
            "id" => 2,
            "title" => "Ultrasound"
        ]
    ]);
});

Route::get('/faqs', [App\Http\Controllers\Admin\FaqController::class, 'getPublicFaqs']);
Route::get('/faqs/category/{slug}', [App\Http\Controllers\Admin\FaqController::class, 'getPublicFaqsBySlug']);
Route::get('/blogs', [App\Http\Controllers\BlogApiController::class, 'index']);
Route::get('/blogs/{slug}', [App\Http\Controllers\BlogApiController::class, 'show']);
Route::get('/contact', [App\Http\Controllers\Admin\ContactController::class, 'getPublicContact']);
Route::get('/categories', [App\Http\Controllers\Admin\CategoryController::class, 'getPublicCategories']);
Route::get('/categories/{id}/subcategories', [App\Http\Controllers\Admin\CategoryController::class, 'getSubCategories']);
Route::get('/categories/slug/{slug}/subcategories', [App\Http\Controllers\Admin\CategoryController::class, 'getSubCategoriesBySlug']);
Route::get('/services/category/{slug}', [App\Http\Controllers\Admin\ServiceController::class, 'getPublicServicesBySlug']);
Route::get('/services/{categorySlug}/{serviceSlug}', [App\Http\Controllers\Admin\ServiceController::class, 'getPublicServiceBySlug']);
Route::get('/cms/page/{page}', [App\Http\Controllers\Admin\CmsController::class, 'getPublicCmsByPage']);
Route::get('/cms/{id}', [App\Http\Controllers\Admin\CmsController::class, 'getPublicCmsById']);