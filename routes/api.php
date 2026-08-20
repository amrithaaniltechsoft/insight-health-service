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
Route::get('/reviews', [App\Http\Controllers\ReviewApiController::class, 'index']);
Route::post('/enquiries', [App\Http\Controllers\EnquiryApiController::class, 'store']);
Route::get('/seos/{page}', [App\Http\Controllers\Admin\SeoController::class, 'getPublicSeoByPage']);

// Auth API routes
Route::post('/auth/send-otp', [App\Http\Controllers\Api\AuthController::class, 'sendOtp']);
Route::post('/auth/verify-otp', [App\Http\Controllers\Api\AuthController::class, 'verifyOtp']);
Route::post('/auth/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
Route::put('/auth/profile', [App\Http\Controllers\Api\AuthController::class, 'updateProfile']);

// Isolated Next.js Administration API Routes
use App\Http\Controllers\AdminApi;

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminApi\AuthController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/me', [AdminApi\AuthController::class, 'me']);
        Route::post('/logout', [AdminApi\AuthController::class, 'logout']);

        Route::get('/overview/stats', [AdminApi\OverviewController::class, 'stats']);
        
        Route::get('/appointments', [AdminApi\AppointmentController::class, 'index']);
        Route::post('/appointments', [AdminApi\AppointmentController::class, 'store']);
        Route::put('/appointments/{id}', [AdminApi\AppointmentController::class, 'update']);
        Route::get('/appointments/calendar', [AdminApi\AppointmentController::class, 'calendar']);

        Route::get('/patients', [AdminApi\PatientController::class, 'index']);
        Route::post('/patients', [AdminApi\PatientController::class, 'store']);
        Route::get('/patients/{id}', [AdminApi\PatientController::class, 'show']);
        Route::put('/patients/{id}', [AdminApi\PatientController::class, 'update']);

        Route::get('/customers', [AdminApi\PatientController::class, 'index']);
        Route::post('/customers', [AdminApi\PatientController::class, 'store']);
        Route::get('/customers/{id}', [AdminApi\PatientController::class, 'show']);
        Route::put('/customers/{id}', [AdminApi\PatientController::class, 'update']);

        Route::get('/clinical-notes/{appointmentId}', [AdminApi\ClinicalNoteController::class, 'showByAppointment']);
        Route::post('/clinical-notes/{appointmentId}', [AdminApi\ClinicalNoteController::class, 'storeOrUpdate']);

        Route::get('/staff', [AdminApi\StaffController::class, 'index']);
        Route::post('/staff', [AdminApi\StaffController::class, 'store']);
        Route::put('/staff/{id}', [AdminApi\StaffController::class, 'update']);

        Route::get('/clinics', [AdminApi\ClinicController::class, 'index']);
        Route::post('/clinics', [AdminApi\ClinicController::class, 'store']);

        Route::get('/payments', [AdminApi\PaymentController::class, 'index']);
        Route::post('/payments', [AdminApi\PaymentController::class, 'store']);

        Route::get('/services', [AdminApi\ServiceController::class, 'index']);
        Route::put('/services/{id}', [AdminApi\ServiceController::class, 'update']);

        Route::get('/schedules', [AdminApi\ScheduleController::class, 'index']);
        Route::post('/schedules', [AdminApi\ScheduleController::class, 'store']);

        Route::get('/reports/analytics', [AdminApi\ReportController::class, 'analytics']);

        Route::get('/notifications', [AdminApi\NotificationController::class, 'index']);
        Route::put('/notifications/{id}/read', [AdminApi\NotificationController::class, 'markAsRead']);
    });
});