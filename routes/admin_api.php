<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminApi;

/*
|--------------------------------------------------------------------------
| Next.js Administration Portal API Routes
|--------------------------------------------------------------------------
| All routes in this file are automatically prefixed with 'admin'
| and served under /api/admin/*
|
*/

// Public Admin Auth Routes
Route::post('/login', [AdminApi\AuthController::class, 'login']);

// Protected Admin API Routes (Sanctum Auth)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [AdminApi\AuthController::class, 'me']);
    Route::post('/logout', [AdminApi\AuthController::class, 'logout']);
    Route::put('/change-password', [AdminApi\AuthController::class, 'changePassword']);

    // Overview & Dashboard Analytics
    Route::get('/overview/stats', [AdminApi\OverviewController::class, 'stats']);

    // Appointments Management
    Route::get('/appointments/booked-slots', [AdminApi\AppointmentController::class, 'bookedSlots']);
    Route::get('/appointments', [AdminApi\AppointmentController::class, 'index']);
    Route::post('/appointments', [AdminApi\AppointmentController::class, 'store']);
    Route::put('/appointments/{id}', [AdminApi\AppointmentController::class, 'update']);
    Route::delete('/appointments/{id}', [AdminApi\AppointmentController::class, 'destroy']);
    Route::get('/appointments/calendar', [AdminApi\AppointmentController::class, 'calendar']);

    // Patients & Customers Directory
    Route::get('/patients/check-email', [AdminApi\PatientController::class, 'checkEmail']);
    Route::get('/patients/search', [AdminApi\PatientController::class, 'search']);
    Route::post('/patients/create-under-customer', [AdminApi\PatientController::class, 'createUnderCustomer']);
    Route::post('/patients/create-with-customer', [AdminApi\PatientController::class, 'createWithCustomer']);
    Route::get('/patients', [AdminApi\PatientController::class, 'patientsIndex']);
    Route::post('/patients', [AdminApi\PatientController::class, 'store']);
    Route::get('/patients/{id}', [AdminApi\PatientController::class, 'showPatient']);
    Route::put('/patients/{id}', [AdminApi\PatientController::class, 'updatePatient']);

    // Patient Medical Files & Attachments
    Route::get('/patients/{patientId}/medical-files', [AdminApi\PatientMedicalFileController::class, 'index']);
    Route::post('/patients/{patientId}/medical-files', [AdminApi\PatientMedicalFileController::class, 'store']);
    Route::delete('/patients/{patientId}/medical-files/{fileId}', [AdminApi\PatientMedicalFileController::class, 'destroy']);

    // Patient Structured Clinical Reports
    Route::get('/patients/{patientId}/clinical-reports', [AdminApi\PatientClinicalReportController::class, 'index']);
    Route::post('/patients/{patientId}/clinical-reports', [AdminApi\PatientClinicalReportController::class, 'store']);
    Route::get('/patients/{patientId}/clinical-reports/{reportId}', [AdminApi\PatientClinicalReportController::class, 'show']);
    Route::get('/patients/{patientId}/clinical-reports/{reportId}/pdf', [AdminApi\PatientClinicalReportController::class, 'pdf']);
    Route::delete('/patients/{patientId}/clinical-reports/{reportId}', [AdminApi\PatientClinicalReportController::class, 'destroy']);
    // Report-type picker options: the patient's booked services minus the ones
    // that already have a report.
    Route::get('/patients/{patientId}/clinical-report-options', [AdminApi\PatientClinicalReportController::class, 'reportOptions']);

    Route::get('/customers', [AdminApi\PatientController::class, 'customersIndex']);
    Route::post('/customers', [AdminApi\PatientController::class, 'store']);
    Route::get('/customers/{id}', [AdminApi\PatientController::class, 'showCustomer']);
    Route::put('/customers/{id}', [AdminApi\PatientController::class, 'updateCustomer']);

    // Clinical Records & Notes
    Route::get('/clinical-notes/{appointmentId}', [AdminApi\ClinicalNoteController::class, 'showByAppointment']);
    Route::post('/clinical-notes/{appointmentId}', [AdminApi\ClinicalNoteController::class, 'storeOrUpdate']);
    Route::get('/patients/{patientId}/clinical-notes', [AdminApi\ClinicalNoteController::class, 'indexByPatient']);
    Route::post('/patients/{patientId}/clinical-notes', [AdminApi\ClinicalNoteController::class, 'storeForPatient']);

    // Staff Directory & Roster
    Route::get('/staff', [AdminApi\StaffController::class, 'index']);
    Route::post('/staff', [AdminApi\StaffController::class, 'store']);
    Route::put('/staff/{id}', [AdminApi\StaffController::class, 'update']);
    Route::put('/staff/{id}/reset-password', [AdminApi\StaffController::class, 'resetPassword']);

    // Clinics Management
    Route::get('/clinics', [AdminApi\ClinicController::class, 'index']);
    Route::post('/clinics', [AdminApi\ClinicController::class, 'store']);

    // Payment Ledgers
    Route::get('/payments', [AdminApi\PaymentController::class, 'index']);
    Route::post('/payments', [AdminApi\PaymentController::class, 'store']);
    Route::post('/payments/{id}/refund', [AdminApi\PaymentController::class, 'refund']);

    // Clinic Services Catalog
    Route::get('/services', [AdminApi\ServiceController::class, 'index']);
    Route::post('/services', [AdminApi\ServiceController::class, 'store']);
    Route::put('/services/{id}', [AdminApi\ServiceController::class, 'update']);
    Route::get('/categories', [AdminApi\ServiceController::class, 'categories']);

    // Schedules
    Route::get('/schedules', [AdminApi\ScheduleController::class, 'index']);
    Route::post('/schedules', [AdminApi\ScheduleController::class, 'store']);

    // System Reports & Analytics
    Route::get('/reports/analytics', [AdminApi\ReportController::class, 'analytics']);

    // System Notifications
    Route::get('/notifications', [AdminApi\NotificationController::class, 'index']);
    Route::put('/notifications/mark-all-read', [AdminApi\NotificationController::class, 'markAllAsRead']);
    Route::put('/notifications/{id}/read', [AdminApi\NotificationController::class, 'markAsRead']);

    // Global Clinic Settings
    Route::get('/settings', [AdminApi\SettingController::class, 'show']);
    Route::put('/settings', [AdminApi\SettingController::class, 'update']);
});
