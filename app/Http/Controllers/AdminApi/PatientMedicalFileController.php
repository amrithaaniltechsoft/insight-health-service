<?php

namespace App\Http\Controllers\AdminApi;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientMedicalFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PatientMedicalFileController extends Controller
{
    public function index($patientId)
    {
        $rawId = intval(preg_replace('/[^0-9]/', '', $patientId));

        $patient = Patient::where('id', $patientId)
            ->orWhere('id', $rawId)
            ->orWhere('patient_code', $patientId)
            ->firstOrFail();

        $files = PatientMedicalFile::where('patient_id', $patient->id)
            ->with(['uploader', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($f) {
                return [
                    'id' => $f->id,
                    'patient_id' => $f->patient_id,
                    'title' => $f->title,
                    'file_name' => $f->file_name,
                    'file_type' => $f->file_type,
                    'mime_type' => $f->mime_type,
                    'file_size' => $f->file_size,
                    'formatted_size' => $this->formatBytes($f->file_size),
                    'notes' => $f->notes,
                    'url' => asset('storage/' . $f->file_path),
                    'uploaded_by' => $f->uploader ? trim($f->uploader->first_name . ' ' . $f->uploader->last_name) : 'System',
                    'created_at' => $f->created_at ? $f->created_at->format('Y-m-d H:i') : '',
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $files
        ]);
    }

    public function store(Request $request, $patientId)
    {
        $rawId = intval(preg_replace('/[^0-9]/', '', $patientId));

        $patient = Patient::where('id', $patientId)
            ->orWhere('id', $rawId)
            ->orWhere('patient_code', $patientId)
            ->firstOrFail();

        $request->validate([
            'file' => 'required|file|max:20480', // max 20MB
            'title' => 'required|string|max:255',
            'file_type' => 'nullable|string',
            'notes' => 'nullable|string',
            'appointment_id' => 'nullable',
        ]);

        $uploadedFile = $request->file('file');
        $fileName = $uploadedFile->getClientOriginalName();
        $mimeType = $uploadedFile->getClientMimeType();
        $fileSize = $uploadedFile->getSize();

        // Store file in public storage directory
        $path = $uploadedFile->store('medical_files', 'public');

        $staffId = null;
        if (auth()->check()) {
            $user = auth()->user();
            // If authenticated staff member
            if (isset($user->staff_id)) {
                $staffId = $user->staff_id;
            }
        }

        $record = PatientMedicalFile::create([
            'patient_id' => $patient->id,
            'appointment_id' => $request->input('appointment_id') ? intval(preg_replace('/[^0-9]/', '', $request->input('appointment_id'))) : null,
            'uploaded_by_staff_id' => $staffId,
            'title' => $request->input('title'),
            'file_name' => $fileName,
            'file_path' => $path,
            'file_type' => $request->input('file_type', 'report'),
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Medical file uploaded successfully',
            'data' => [
                'id' => $record->id,
                'title' => $record->title,
                'file_name' => $record->file_name,
                'file_type' => $record->file_type,
                'url' => asset('storage/' . $record->file_path),
                'formatted_size' => $this->formatBytes($record->file_size),
                'created_at' => $record->created_at ? $record->created_at->format('Y-m-d H:i') : '',
            ]
        ], 201);
    }

    public function destroy($patientId, $fileId)
    {
        $file = PatientMedicalFile::where('id', $fileId)->firstOrFail();

        if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Medical file deleted successfully'
        ]);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
