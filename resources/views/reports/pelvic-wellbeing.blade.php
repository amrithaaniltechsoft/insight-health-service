<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $v['title'] ?: 'PELVIC ULTRASOUND SCAN REPORT' }}</title>
<style>
    @page { size: A4 portrait; margin: 0; }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        padding: 0;
        font-family: Helvetica, Arial, sans-serif;
        font-size: 10px;
        color: #000;
    }
    .report {
        width: 100%;
        border: 1px solid #000;
        border-collapse: collapse;
    }
    .letterhead {
        display: block;
        width: 100%;
        border-bottom: 2px solid #000;
        margin: 0;
        padding: 0;
    }
    .doc-title {
        text-align: center;
        font-weight: bold;
        font-size: 14px;
        padding: 6px 10px 5px 10px;
        letter-spacing: 0.5px;
    }
    table.grid {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    table.grid td {
        border: 1px solid #000;
        padding: 4px 6px;
        vertical-align: middle;
        font-size: 10px;
    }
    td.lbl {
        background-color: #e6deb4;
        font-weight: bold;
        white-space: nowrap;
    }
    .verified {
        border: 1px solid #000;
        text-align: center;
        font-weight: bold;
        padding: 4px;
        font-size: 10px;
    }
    .footer-band {
        display: block;
        width: 100%;
        border-top: 2px solid #000;
        margin-top: 8px;
        padding: 5px 8px;
        text-align: center;
        line-height: 1.4;
        background-color: #e5b243;
        @if (!empty($v['footerBg']))
            background-image: url("{{ $v['footerBg'] }}");
            background-size: 100% 100%;
            background-repeat: no-repeat;
        @endif
        font-size: 13px;
    }
</style>
</head>
<body>
    <div class="report">
        {{-- Common Header Letterhead --}}
        @include('reports.partials.header')

        <div class="doc-title">{{ $v['title'] ?: 'PELVIC ULTRASOUND SCAN REPORT' }}</div>

        {{-- Patient Information --}}
        <table class="grid">
            <colgroup>
                <col style="width: 16%;">
                <col style="width: 29%;">
                <col style="width: 13%;">
                <col style="width: 18%;">
                <col style="width: 12%;">
                <col style="width: 12%;">
            </colgroup>
            <tbody>
                <tr>
                    <td class="lbl">Patient Name</td>
                    <td>{{ $v['patient_name'] }}</td>
                    <td class="lbl">Date of Birth</td>
                    <td>{{ $v['dob'] }}</td>
                    <td class="lbl">Patient ID</td>
                    <td>{{ $v['patient_id'] }}</td>
                </tr>
                <tr>
                    <td class="lbl">Address</td>
                    <td colspan="3">{{ $v['address'] }}</td>
                    <td class="lbl">NHS No</td>
                    <td>{{ $v['nhs_no'] }}</td>
                </tr>
                <tr>
                    <td class="lbl">Study Description</td>
                    <td>{{ $v['study_description'] }}</td>
                    <td class="lbl">Study Date</td>
                    <td>{{ $v['study_date'] }}</td>
                    <td class="lbl">Referrer</td>
                    <td>{{ $v['referrer'] }}</td>
                </tr>
            </tbody>
        </table>

        <div style="padding: 6px 10px 4px 10px;">
            {{-- Decontamination and Consent --}}
            <div style="padding: 6px 10px; background-color: #fafafa; border: 1px solid #000; font-size: 9.5px; line-height: 1.45;">
                {{ $v['decontamination'] }}
            </div>

            {{-- Clinical Indication --}}
            <table class="grid" style="margin-top: 6px;">
                <tr>
                    <td class="lbl" style="width: 18%;">Clinical Indication</td>
                    <td style="width: 82%;">{{ $v['clinical_indication'] }}</td>
                </tr>
            </table>

            {{-- Obstetric / Menstrual History --}}
            <table class="grid" style="margin-top: 5px;">
                <colgroup>
                    <col style="width: 10%;">
                    <col style="width: 33%;">
                    <col style="width: 19%;">
                    <col style="width: 19%;">
                    <col style="width: 19%;">
                </colgroup>
                <tr>
                    <td class="lbl">LMP</td>
                    <td>{{ $v['lmp'] }}</td>
                    <td style="text-align: center;">
                        <span style="font-weight: bold;">[{{ !empty($v['para']) && $v['para'] !== 'No' ? 'X' : '&nbsp;&nbsp;' }}]</span> Para
                    </td>
                    <td style="text-align: center;">
                        <span style="font-weight: bold;">[{{ !empty($v['normal_births']) && $v['normal_births'] !== 'No' ? 'X' : '&nbsp;&nbsp;' }}]</span> Normal births
                    </td>
                    <td style="text-align: center;">
                        <span style="font-weight: bold;">[{{ !empty($v['cs']) && $v['cs'] !== 'No' ? 'X' : '&nbsp;&nbsp;' }}]</span> CS
                    </td>
                </tr>
            </table>

            {{-- Scan Findings --}}
            <table class="grid" style="margin-top: 6px;">
                <tr>
                    <td class="lbl" style="width: 18%; vertical-align: top; padding-top: 8px;">Scan Findings</td>
                    <td style="width: 82%; padding: 8px 10px; line-height: 1.55; min-height: 110px;">
                        {!! nl2br(e($v['scan_findings'])) !!}
                    </td>
                </tr>
            </table>

            {{-- Impression --}}
            <table class="grid" style="margin-top: 6px;">
                <tr>
                    <td class="lbl" style="width: 18%; vertical-align: top; padding-top: 8px;">Impression</td>
                    <td style="width: 82%; padding: 8px 10px; line-height: 1.5; min-height: 40px;">
                        {!! nl2br(e($v['impression'] ?: 'None noted.')) !!}
                    </td>
                </tr>
            </table>

            {{-- Disclaimer --}}
            <div style="border: 1px solid #000; padding: 6px 10px; margin-top: 6px; font-size: 8.5px; line-height: 1.35; background-color: #fff; color: #222;">
                {{ $v['disclaimer'] }}
            </div>

            {{-- Signatures --}}
            <div style="font-weight: bold; font-size: 10px; margin-top: 6px; margin-bottom: 2px;">Reported &amp; Signed By –</div>
            <table class="grid">
                <colgroup>
                    <col style="width: 16%;">
                    <col style="width: 34%;">
                    <col style="width: 16%;">
                    <col style="width: 34%;">
                </colgroup>
                <tbody>
                    <tr>
                        <td class="lbl">Sonographer</td>
                        <td>{{ $v['sonographer'] }}</td>
                        <td class="lbl">Chaperone</td>
                        <td>{{ $v['chaperone'] }}</td>
                    </tr>
                    <tr>
                        <td class="verified" colspan="4">Electronically Verified: {{ $v['status'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Common Footer Band --}}
        @include('reports.partials.footer')
    </div>
</body>
</html>
