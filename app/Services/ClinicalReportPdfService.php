<?php

namespace App\Services;

use App\Models\PatientClinicalReport;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

/**
 * Renders a clinical report to a PDF with dompdf and stores it on the local
 * disk, so a ready-made file exists as soon as the admin saves the report and
 * can simply be streamed later instead of being rendered on every request.
 */
class ClinicalReportPdfService
{
    /** Disk the generated PDFs live on (web-accessible via /storage). */
    private const DISK = 'public';

    /** Relative (to the disk root) path of a report's generated PDF. */
    public function relativePath(PatientClinicalReport $report): string
    {
        return 'reports/' . $report->report_code . '.pdf';
    }

    /** Absolute path of the stored PDF, or null when it has not been generated. */
    public function path(PatientClinicalReport $report): ?string
    {
        $relative = $this->relativePath($report);

        return Storage::disk(self::DISK)->exists($relative)
            ? Storage::disk(self::DISK)->path($relative)
            : null;
    }

    /** Public URL of the stored PDF (e.g. http://host/storage/reports/REP-00001.pdf). */
    public function url(PatientClinicalReport $report): string
    {
        return Storage::disk(self::DISK)->url($this->relativePath($report));
    }

    /**
     * Builds the PDF bytes for a report. The Early Pregnancy template is the
     * only layout wired up so far.
     */
    public function build(PatientClinicalReport $report): string
    {
        $report->loadMissing('patient');

        $html = view('reports.early-pregnancy', ['v' => $this->values($report)])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', base_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    /** Generates the PDF and writes it to storage, returning the relative path. */
    public function generate(PatientClinicalReport $report): string
    {
        $relative = $this->relativePath($report);

        Storage::disk(self::DISK)->put($relative, $this->build($report));

        return $relative;
    }

    /**
     * Flattens the stored report/patient fields into the display values the
     * template needs, mirroring the patient-facing report page.
     */
    private function values(PatientClinicalReport $report): array
    {
        $data = is_array($report->report_data) ? $report->report_data : [];
        $patient = $report->patient;

        $name = $patient
            ? trim(collect([$patient->title, $patient->first_name, $patient->last_name])->filter()->implode(' '))
            : '';

        $fetalNumber = $this->text($data['fetalNumber'] ?? '');
        $isMultiple = strtolower($fetalNumber) === 'multiple';

        return [
            'title'             => trim(preg_replace('/\s*\(PDF\)\s*$/i', '', (string) $report->display_title)),
            'patient_name'      => $name,
            'dob'               => $this->date($patient?->dob),
            'patient_id'        => $patient?->patient_code ?: 'IHSUS',
            'study_date'        => $this->date($data['studyDate'] ?? $report->created_at),
            'contact_no'        => $this->text($data['contactNo'] ?? ''),
            'consent'           => $this->text($data['consentToScan'] ?? '') ?: 'Yes',
            'clinical_indication' => $this->text($data['clinicalIndication'] ?? ''),
            'scan_method'       => $this->text($data['scanMethod'] ?? '') ?: 'Transabdominal',
            'fetal_heart'       => $this->text($data['fetalHeart'] ?? '') ?: 'Present',
            'placenta'          => $this->text($data['placenta'] ?? ''),
            'singleton'         => $isMultiple ? 'No' : 'Yes',
            'multiple'          => $isMultiple ? $fetalNumber : '',
            'placental_position' => $this->text($data['placentalPosition'] ?? ''),
            'gestation_weeks'   => $this->text($data['gestationWeeks'] ?? ''),
            'gestation_days'    => $this->text($data['gestationDays'] ?? ''),
            'edd'               => $this->date($data['edd'] ?? ''),
            'comments'          => $this->text($data['comments'] ?? ''),
            'sonographer'       => $this->text($data['sonographer'] ?? ''),
            'chaperone'         => $this->text($data['chaperone'] ?? ''),
            'status'            => (string) ($report->status ?? ''),
            'logoSrc'           => $this->imageDataUri('reports/letterhead.jpg'),
            // Gold footer band artwork (text stripped) used as the CSS background
            // so the contact details can stay as real, resizable text.
            'footerBg'          => $this->imageDataUri('reports/footer-band-clean.jpg'),
        ];
    }

    private function text(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }
        if ($value === true) {
            return 'Yes';
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return trim((string) $value);
    }

    /** Normalises a Carbon date or "YYYY-MM-DD" string to DD/MM/YYYY. */
    private function date(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    /** Embeds a public image as a data URI so dompdf needs no filesystem access. */
    private function imageDataUri(string $publicPath): string
    {
        $absolute = public_path($publicPath);
        if (!is_file($absolute)) {
            return '';
        }

        $mime = str_ends_with(strtolower($absolute), '.png') ? 'image/png' : 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($absolute));
    }
}
