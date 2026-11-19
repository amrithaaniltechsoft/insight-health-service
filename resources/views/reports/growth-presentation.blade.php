<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $v['title'] ?: 'GROWTH & PRESENTATION SCAN REPORT' }}</title>
<style>
    @page { size: A4 portrait; margin: 0; }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        padding: 0;
        font-family: Helvetica, Arial, sans-serif;
        font-size: 10.5px;
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
        font-size: 10.5px;
    }
    td.lbl {
        background-color: #e6deb4;
        font-weight: bold;
        white-space: nowrap;
    }
    .section-divider {
        border-top: 1px solid #000;
        margin: 0;
        padding: 0;
    }
    .section-title {
        font-weight: bold;
        font-size: 11px;
        text-transform: uppercase;
        padding: 6px 0 4px 0;
    }
    .val-box {
        border: 1px solid #000;
        background-color: #fff;
        height: 20px;
        line-height: 20px;
        padding: 0 6px;
        display: inline-block;
        vertical-align: middle;
        text-align: center;
        font-size: 10.5px;
    }
    table.findings-layout {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    table.findings-layout td {
        border: none;
        padding: 4px 4px;
        vertical-align: middle;
        font-size: 10.5px;
    }
    table.biometry-table {
        width: 90%;
        border-collapse: collapse;
    }
    table.biometry-table td {
        border: none;
        padding: 4px 2px;
        vertical-align: middle;
        font-size: 10.5px;
    }
    table.biometry-table td.box-cell {
        border: 1px solid #000;
        background-color: #fff;
        height: 20px;
        text-align: center;
        padding: 0 4px;
        font-size: 10.5px;
    }
    td.verified {
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
        margin: 0;
        padding: 5px 8px;
        text-align: center;
        line-height: 1.4;
        background-color: #e5b243;
        @if (!empty($v['footerBg']))
            background-image: url("{{ $v['footerBg'] }}");
            background-size: 100% 100%;
            background-repeat: no-repeat;
        @endif
        font-size: 13.5px;
    }
</style>
</head>
<body>
    <div class="report">
        @include('reports.partials.header')

        <div class="doc-title">{{ $v['title'] ?: 'GROWTH & PRESENTATION SCAN REPORT' }}</div>

        {{-- Patient Information --}}
        <table class="grid">
            <colgroup>
                <col style="width: 15%;">
                <col style="width: 25%;">
                <col style="width: 12%;">
                <col style="width: 18%;">
                <col style="width: 15%;">
                <col style="width: 15%;">
            </colgroup>
            <tbody>
                <tr>
                    <td class="lbl">Patient Name</td>
                    <td>{{ $v['patient_name'] }}</td>
                    <td class="lbl">DOB</td>
                    <td>{{ $v['dob'] }}</td>
                    <td class="lbl">Patient ID</td>
                    <td>{{ $v['patient_id'] }}</td>
                </tr>
                <tr>
                    <td class="lbl">Study Date</td>
                    <td>{{ $v['study_date'] }}</td>
                    <td class="lbl">Contact No</td>
                    <td>{{ $v['contact_no'] }}</td>
                    <td class="lbl">Consent to Scan</td>
                    <td>{{ $v['consent'] }}</td>
                </tr>
            </tbody>
        </table>

        {{-- White open area with bottom divider line --}}
        <div style="height: 48px; border-bottom: 1px solid #000; padding: 6px 10px; font-size: 10px; color: #555;">
            @if (!empty($v['clinical_indication']))
                <strong>Clinical Indication:</strong> {{ $v['clinical_indication'] }}
            @endif
        </div>

        {{-- Findings Section --}}
        <div style="padding: 10px 14px 10px 14px;">
            <table class="findings-layout">
                <colgroup>
                    <col style="width: 50%;">
                    <col style="width: 50%;">
                </colgroup>
                <tbody>
                    {{-- Row 1: EDD --}}
                    <tr>
                        <td colspan="2" style="padding-bottom: 6px;">
                            <span style="font-weight: bold; display: inline-block; width: 170px;">ESTIMATED DUE DATE</span>
                            <span class="val-box" style="width: 150px;">{{ $v['edd'] }}</span>
                        </td>
                    </tr>

                    {{-- Row 2: Gestational Age --}}
                    <tr>
                        <td colspan="2" style="padding-bottom: 6px;">
                            <span style="font-weight: bold; display: inline-block; width: 170px;">GESTATIONAL AGE</span>
                            <span class="val-box" style="width: 60px;">{{ $v['gestation_weeks'] }}</span>
                            <span style="padding: 0 4px;">Weeks</span>
                            <span style="padding: 0 4px;">and</span>
                            <span class="val-box" style="width: 60px;">{{ $v['gestation_days'] }}</span>
                            <span style="padding: 0 4px;">Days</span>
                        </td>
                    </tr>

                    {{-- Row 3: Fetal Heart & Fetal Movement --}}
                    <tr>
                        <td style="padding-bottom: 6px;">
                            <span style="font-weight: bold; display: inline-block; width: 170px;">FETAL HEART</span>
                            <span class="val-box" style="width: 150px;">{{ $v['fetal_heart'] }}</span>
                        </td>
                        <td style="padding-bottom: 6px;">
                            <span style="font-weight: bold; display: inline-block; width: 145px;">FETAL MOVEMENT</span>
                            <span class="val-box" style="width: 160px;">{{ $v['fetal_movement'] }}</span>
                        </td>
                    </tr>

                    {{-- Row 4: Placenta & Placenta Site --}}
                    <tr>
                        <td style="padding-bottom: 6px;">
                            <span style="font-weight: bold; display: inline-block; width: 170px;">PLACENTA</span>
                            <span class="val-box" style="width: 150px;">{{ $v['placenta'] }}</span>
                        </td>
                        <td style="padding-bottom: 6px;">
                            <span style="font-weight: bold; display: inline-block; width: 145px;">PLACENTA SITE</span>
                            <span class="val-box" style="width: 160px;">{{ $v['placenta_site'] }}</span>
                        </td>
                    </tr>

                    {{-- Row 5: Fetal Presentation --}}
                    <tr>
                        <td colspan="2" style="padding-bottom: 6px;">
                            <span style="font-weight: bold; display: inline-block; width: 170px;">FETAL PRESENTATION</span>
                            <span class="val-box" style="width: 150px;">{{ $v['fetal_presentation'] }}</span>
                        </td>
                    </tr>

                    {{-- Row 6: Amniotic Fluid & End Diastolic Flow --}}
                    <tr>
                        <td style="padding-bottom: 6px;">
                            <span style="font-weight: bold; display: inline-block; width: 170px;">AMNIOTIC FLUID (MPD)</span>
                            <span class="val-box" style="width: 60px;">{{ $v['amniotic_fluid_mpd'] }}</span>
                            <span style="padding-left: 4px;">cm</span>
                        </td>
                        <td style="padding-bottom: 6px;">
                            <span style="font-weight: bold; display: inline-block; width: 145px;">END DIASTOLIC FLOW</span>
                            <span class="val-box" style="width: 160px;">{{ $v['end_diastolic_flow'] }}</span>
                        </td>
                    </tr>

                    {{-- Row 7: Pulsatility Index & Resistive Index --}}
                    <tr>
                        <td>
                            <span style="font-weight: bold; display: inline-block; width: 170px;">PULSATALITY INDEX (PI)</span>
                            <span class="val-box" style="width: 60px;">{{ $v['pi'] }}</span>
                        </td>
                        <td>
                            <span style="font-weight: bold; display: inline-block; width: 145px;">RESISTIVE INDEX (RI)</span>
                            <span class="val-box" style="width: 60px;">{{ $v['ri'] }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section-divider"></div>

        {{-- Fetal Biometry Section --}}
        <div style="padding: 8px 14px 10px 14px;">
            <div class="section-title">FETAL BIOMETRY</div>
            <table class="biometry-table">
                <colgroup>
                    <col style="width: 32%;">
                    <col style="width: 11%;">
                    <col style="width: 5%;">
                    <col style="width: 6%;">
                    <col style="width: 14%;">
                    <col style="width: 8%;">
                    <col style="width: 14%;">
                    <col style="width: 10%;">
                </colgroup>
                <tbody>
                    <tr>
                        <td style="font-weight: bold;">HEAD CIRCUMFERENCE</td>
                        <td class="box-cell">{{ $v['hc'] }}</td>
                        <td style="padding-left: 3px;">mm</td>
                        <td></td>
                        <td class="box-cell">{{ $v['hc_weeks'] }}</td>
                        <td style="padding-left: 3px;">Weeks</td>
                        <td class="box-cell">{{ $v['hc_days'] }}</td>
                        <td style="padding-left: 3px;">Days</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">ABDOMINAL CIRCUMFERENCE</td>
                        <td class="box-cell">{{ $v['ac'] }}</td>
                        <td style="padding-left: 3px;">mm</td>
                        <td></td>
                        <td class="box-cell">{{ $v['ac_weeks'] }}</td>
                        <td style="padding-left: 3px;">Weeks</td>
                        <td class="box-cell">{{ $v['ac_days'] }}</td>
                        <td style="padding-left: 3px;">Days</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">FEMUR LENGTH</td>
                        <td class="box-cell">{{ $v['fl'] }}</td>
                        <td style="padding-left: 3px;">mm</td>
                        <td></td>
                        <td class="box-cell">{{ $v['fl_weeks'] }}</td>
                        <td style="padding-left: 3px;">Weeks</td>
                        <td class="box-cell">{{ $v['fl_days'] }}</td>
                        <td style="padding-left: 3px;">Days</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section-divider"></div>

        {{-- Comments Section --}}
        <div style="padding: 8px 14px 16px 14px;">
            <div class="section-title">COMMENTS</div>
            <div style="font-size: 10.5px; line-height: 1.5; min-height: 38px;">
                {{ $v['comments'] }}
            </div>
        </div>

        {{-- Signatures Table --}}
        <table class="grid">
            <colgroup>
                <col style="width: 18%;">
                <col style="width: 32%;">
                <col style="width: 18%;">
                <col style="width: 32%;">
            </colgroup>
            <tbody>
                <tr>
                    <td class="lbl">Sonograher</td>
                    <td>{{ $v['sonographer'] }}</td>
                    <td class="lbl">Chaperone</td>
                    <td>{{ $v['chaperone'] }}</td>
                </tr>
                <tr>
                    <td class="verified" colspan="4">Electronically Verified: {{ $v['status'] }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Common Footer Band --}}
        @include('reports.partials.footer')
    </div>
</body>
</html>