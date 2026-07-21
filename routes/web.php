<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CmsController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

/*Route::get('/', function () {
    return view('welcome');
});*/

use App\Models\Category;

Route::get('/dashboard', function () {
    $categories = Category::withCount('services')->orderByRaw('FIELD(id, 4, 1, 2, 3, 5)')->get();
    return view('dashboard', compact('categories'));
})->middleware(['auth', 'verified'])->name('dashboard');

// Invoice PDF
use App\Http\Controllers\InvoiceController;
Route::get('/invoices/{id}/pdf', [InvoiceController::class, 'preview'])->name('invoice.pdf');

// Blog routes (public)
Route::get('/blogs', [BlogController::class, 'index'])->name('blogs.index');
Route::get('/blogs/{id}', [BlogController::class, 'show'])->name('blogs.show');
Route::get('/blogs/category/{categoryId}', [BlogController::class, 'byCategory'])->name('blogs.category');

// FAQ Admin Routes (protected)
Route::middleware('auth')->prefix('admin/faqs')->group(function () {
    Route::get('/', [FaqController::class, 'adminIndex'])->name('faqs.admin.index');
    Route::get('/data', [FaqController::class, 'getFaqs'])->name('faqs.admin.data');
    Route::get('/{id}/show', [FaqController::class, 'getFaqById'])->name('faqs.admin.show');
    Route::post('/', [FaqController::class, 'store'])->name('faqs.admin.store');
    Route::put('/{id}', [FaqController::class, 'update'])->name('faqs.admin.update');
    Route::delete('/{id}', [FaqController::class, 'destroy'])->name('faqs.admin.destroy');
});

// Blog Admin Routes (protected)
Route::middleware('auth')->prefix('admin/blogs')->group(function () {
    Route::get('/', [BlogController::class, 'adminIndex'])->name('blogs.admin.index');
    Route::get('/data', [BlogController::class, 'getBlogs'])->name('blogs.admin.data');
    Route::get('/sub-categories/{categoryId}', [BlogController::class, 'getSubCategories'])->name('blogs.admin.subcategories');
    Route::get('/{id}/show', [BlogController::class, 'getBlogById'])->name('blogs.admin.show');
    Route::get('/create', [BlogController::class, 'create'])->name('blogs.admin.create');
    Route::post('/', [BlogController::class, 'store'])->name('blogs.admin.store');
    Route::get('/{id}/edit', [BlogController::class, 'edit'])->name('blogs.admin.edit');
    Route::put('/{id}', [BlogController::class, 'update'])->name('blogs.admin.update');
    Route::delete('/{id}', [BlogController::class, 'destroy'])->name('blogs.admin.destroy');
});

// Contact Admin Routes (protected)
Route::middleware('auth')->prefix('admin/contacts')->group(function () {
    Route::get('/', [ContactController::class, 'adminIndex'])->name('contacts.admin.index');
    Route::put('/', [ContactController::class, 'update'])->name('contacts.admin.update');
});

// CMS Admin Routes (protected)
Route::middleware('auth')->prefix('admin/cms')->group(function () {
    Route::get('/', [CmsController::class, 'adminIndex'])->name('cms.admin.index');
    Route::get('/data', [CmsController::class, 'getCms'])->name('cms.admin.data');
    Route::get('/{id}/show', [CmsController::class, 'getCmsById'])->name('cms.admin.show');
    Route::post('/', [CmsController::class, 'store'])->name('cms.admin.store');
    Route::put('/{id}', [CmsController::class, 'update'])->name('cms.admin.update');
    Route::delete('/{id}', [CmsController::class, 'destroy'])->name('cms.admin.destroy');
});

// Services Admin Routes (protected)
Route::middleware('auth')->prefix('admin/services')->group(function () {
    Route::get('/', [ServiceController::class, 'adminIndex'])->name('services.admin.index');
    Route::get('/data', [ServiceController::class, 'getServices'])->name('services.admin.data');
    Route::get('/category/{categoryId}', [ServiceController::class, 'categoryServices'])->name('services.admin.category');
    Route::get('/category/{categoryId}/data', [ServiceController::class, 'getCategoryServices'])->name('services.admin.category.data');
    Route::get('/{id}/show', [ServiceController::class, 'getServiceById'])->name('services.admin.show');
    Route::post('/', [ServiceController::class, 'store'])->name('services.admin.store');
    Route::post('/import/{categoryId}', [ServiceController::class, 'importExcel'])->name('services.admin.import');
    Route::post('/sub-category/store', [ServiceController::class, 'storeSubCategory'])->name('services.admin.subcategory.store');
    Route::put('/sub-category/{id}', [ServiceController::class, 'updateSubCategory'])->name('services.admin.subcategory.update');
    Route::delete('/sub-category/{id}', [ServiceController::class, 'destroySubCategory'])->name('services.admin.subcategory.destroy');
    Route::put('/{id}', [ServiceController::class, 'update'])->name('services.admin.update');
    Route::delete('/{id}', [ServiceController::class, 'destroy'])->name('services.admin.destroy');
});

// Category Admin Routes (protected)
use App\Http\Controllers\Admin\CategoryController;
Route::middleware('auth')->prefix('admin/categories')->group(function () {
    Route::get('/', [CategoryController::class, 'adminIndex'])->name('categories.admin.index');
    Route::put('/{id}', [CategoryController::class, 'updateCategory'])->name('categories.admin.update');
});

// Shop Admin Routes (protected)
Route::middleware('auth')->prefix('admin/shops')->group(function () {
    Route::get('/', [ShopController::class, 'adminIndex'])->name('shops.admin.index');
    Route::get('/data', [ShopController::class, 'getShops'])->name('shops.admin.data');
    Route::get('/{id}/show', [ShopController::class, 'getShopById'])->name('shops.admin.show');
    Route::post('/', [ShopController::class, 'store'])->name('shops.admin.store');
    Route::put('/{id}', [ShopController::class, 'update'])->name('shops.admin.update');
    Route::delete('/{id}', [ShopController::class, 'destroy'])->name('shops.admin.destroy');
});

// Shop API Routes (for Next.js frontend)
Route::get('/api/shop/products', [ShopController::class, 'apiIndex'])->name('shop.api.index');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/admin/change-password', function () {
        return view('auth.change-password');
    })->name('password.change');
    Route::post('/admin/change-password', [ProfileController::class, 'changePassword'])->name('password.update');
});

require __DIR__.'/auth.php';
