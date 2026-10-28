<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $v['title'] }}</title>
<style>
    @page { size: A4 portrait; margin: 0; }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        padding: 0;
        font-family: Helvetica, Arial, sans-serif;
        font-size: 11px;
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
    .title-bar {
        background-color: #e6deb4;
        border-bottom: 1px solid #000;
        text-align: center;
        font-weight: bold;
        font-size: 15px;
        padding: 9px 10px;
    }
    .findings-title {
        border-bottom: 1px solid #000;
        text-align: center;
        font-weight: bold;
        font-size: 13px;
        padding: 16px 10px;
    }
    table.grid {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    table.grid td {
        border: 1px solid #000;
        padding: 5px 7px;
        vertical-align: middle;
        font-size: 11px;
    }
    td.lbl {
        background-color: #e6deb4;
        font-weight: bold;
        white-space: nowrap;
    }
    td.gap-col { border: none; }
    tr.gap td {
        border: none;
        height: 6px;
        padding: 0;
        font-size: 0;
        line-height: 0;
    }
    td.comments-label {
        border: 1px solid #000;
        border-bottom: none;
        font-weight: bold;
        font-size: 12px;
        padding: 6px 9px;
    }
    td.comments-body {
        border: 1px solid #000;
        background-color: #e6deb4;
        height: 70px;
        padding: 10px;
        vertical-align: top;
        line-height: 1.5;
        font-size: 11px;
    }
    td.verified {
        border: 1px solid #000;
        text-align: center;
        font-weight: bold;
        padding: 6px;
    }
    .footer-band {
        display: block;
        width: 100%;
        border-top: 2px solid #000;
        margin: 0;
        padding: 6px 8px;
        text-align: center;
        line-height: 1.5;
        background-color: #e5b243;
        @if (!empty($v['footerBg']))
            background-image: url("{{ $v['footerBg'] }}");
            background-size: 100% 100%;
            background-repeat: no-repeat;
        @endif
        font-size: 15px;
    }
</style>
</head>
<body>
    <div class="report">
        @if ($v['logoSrc'])
            <img src="{{ $v['logoSrc'] }}" class="letterhead" alt="">
        @endif

        <div class="title-bar">{{ $v['title'] }}</div>

        {{-- Patient information --}}
        <table class="grid">
            <colgroup>
                <col style="width: 10.12%"><col style="width: 9.51%"><col style="width: 7.36%">
                <col style="width: 7.36%"><col style="width: 7.36%"><col style="width: 8.44%">
                <col style="width: 9.51%"><col style="width: 7.36%"><col style="width: 10.42%">
                <col style="width: 9.51%"><col style="width: 13.05%">
            </colgroup>
            <tbody>
                <tr>
                    <td class="lbl" colspan="2">Patient Name</td>
                    <td colspan="3">{{ $v['patient_name'] }}</td>
                    <td class="lbl">DOB</td>
                    <td colspan="2">{{ $v['dob'] }}</td>
                    <td class="lbl">Patient ID</td>
                    <td colspan="2">{{ $v['patient_id'] }}</td>
                </tr>
                <tr>
                    <td class="lbl">Study Date</td>
                    <td colspan="2">{{ $v['study_date'] }}</td>
                    <td class="lbl" colspan="2">Contact No</td>
                    <td colspan="2">{{ $v['contact_no'] }}</td>
                    <td class="lbl" colspan="2">Consent to Scan</td>
                    <td colspan="2">{{ $v['consent'] }}</td>
                </tr>
                <tr>
                    <td class="lbl" colspan="3">Clinical Indication</td>
                    <td colspan="7">{{ $v['clinical_indication'] }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        {{-- Findings title --}}
        <div class="findings-title">{{ $v['title'] }}</div>

        {{-- Findings --}}
        <table class="grid">
            <colgroup>
                <col style="width: 10.12%"><col style="width: 9.51%"><col style="width: 7.36%">
                <col style="width: 7.36%"><col style="width: 7.36%"><col style="width: 8.44%">
                <col style="width: 9.51%"><col style="width: 7.36%"><col style="width: 10.42%">
                <col style="width: 9.51%"><col style="width: 13.05%">
            </colgroup>
            <tbody>
                <tr>
                    <td class="lbl" colspan="2">SCAN METHOD</td>
                    <td colspan="3">{{ $v['scan_method'] }}</td>
                    <td colspan="6"></td>
                </tr>
                <tr class="gap"><td colspan="11"></td></tr>
                <tr>
                    <td class="lbl" colspan="2">FETAL HEART</td>
                    <td colspan="3">{{ $v['fetal_heart'] }}</td>
                    <td class="gap-col"></td>
                    <td class="lbl" colspan="2">PLACENTA</td>
                    <td colspan="2">{{ $v['placenta'] }}</td>
                    <td></td>
                </tr>
                <tr class="gap"><td colspan="11"></td></tr>
                <tr>
                    <td class="lbl" colspan="2">SINGLETON</td>
                    <td>{{ $v['singleton'] }}</td>
                    <td class="lbl" colspan="2">MULTIPLE</td>
                    <td>{{ $v['multiple'] }}</td>
                    <td class="lbl" colspan="2">PLACENTAL POSITION</td>
                    <td colspan="2">{{ $v['placental_position'] }}</td>
                    <td></td>
                </tr>
                <tr class="gap"><td colspan="11"></td></tr>
                <tr>
                    <td class="lbl" colspan="2">GESTATION</td>
                    <td>{{ $v['gestation_weeks'] }}</td>
                    <td>Weeks</td>
                    <td>and</td>
                    <td>{{ $v['gestation_days'] }}</td>
                    <td>Days</td>
                    <td colspan="4"></td>
                </tr>
                <tr class="gap"><td colspan="11"></td></tr>
                <tr>
                    <td class="lbl" colspan="2">ESTIMATED DUE DATE</td>
                    <td colspan="3">{{ $v['edd'] }}</td>
                    <td colspan="6"></td>
                </tr>
                <tr class="gap"><td colspan="11"></td></tr>
                <tr>
                    <td class="lbl" colspan="2">MEASUREMENTS</td>
                    <td style="font-weight: bold; text-align: right;">HC:</td>
                    <td>{{ $v['hc'] }}</td>
                    <td>mm</td>
                    <td style="font-weight: bold; text-align: right;">AC:</td>
                    <td>{{ $v['ac'] }}</td>
                    <td>mm</td>
                    <td style="font-weight: bold; text-align: right;">FL:</td>
                    <td>{{ $v['fl'] }}</td>
                    <td>mm</td>
                </tr>
                <tr class="gap"><td colspan="11"></td></tr>
                <tr>
                    <td class="lbl" colspan="2">FETAL PRESENTATION</td>
                    <td colspan="3">{{ $v['fetal_presentation'] }}</td>
                    <td class="gap-col"></td>
                    <td class="lbl" colspan="2">FETAL MOVEMENTS</td>
                    <td colspan="2">{{ $v['fetal_movements'] }}</td>
                    <td></td>
                </tr>
                <tr class="gap"><td colspan="11"></td></tr>
                <tr>
                    <td class="lbl" colspan="3">AMNIOTIC FLUID (MPD)</td>
                    <td>{{ $v['amniotic_fluid_mpd'] }}</td>
                    <td>cm</td>
                    <td class="gap-col"></td>
                    <td class="lbl" colspan="2">EDF</td>
                    <td colspan="2">{{ $v['edf'] }}</td>
                    <td></td>
                </tr>
                <tr class="gap"><td colspan="11"></td></tr>
                <tr>
                    <td class="lbl" colspan="3">ESTIMATED FETAL WEIGHT</td>
                    <td>{{ $v['efw_oz_lb'] }}</td>
                    <td>Oz / lb</td>
                    <td class="gap-col"></td>
                    <td colspan="2">{{ $v['efw_grams'] }}</td>
                    <td>grams</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="gap"><td colspan="11"></td></tr>
            </tbody>
        </table>

        {{-- Comments / Summary --}}
        <table class="grid">
            <tbody>
                <tr><td class="comments-label" colspan="11">REPORT SUMMARY</td></tr>
                <tr><td class="comments-body" colspan="11">{{ $v['comments'] }}</td></tr>
            </tbody>
        </table>

        {{-- Signatures --}}
        <table class="grid">
            <colgroup>
                <col style="width: 10.12%"><col style="width: 9.51%"><col style="width: 7.36%">
                <col style="width: 7.36%"><col style="width: 7.36%"><col style="width: 8.44%">
                <col style="width: 9.51%"><col style="width: 7.36%"><col style="width: 10.42%">
                <col style="width: 9.51%"><col style="width: 13.05%">
            </colgroup>
            <tbody>
                <tr>
                    <td class="lbl" colspan="2">Sonographer</td>
                    <td colspan="3">{{ $v['sonographer'] }}</td>
                    <td class="lbl" colspan="2">Chaperone</td>
                    <td colspan="4">{{ $v['chaperone'] }}</td>
                </tr>
                <tr>
                    <td class="verified" colspan="11">Electronically Verified: {{ $v['status'] }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Footer contact strip --}}
        <div class="footer-band">
            1A Walsall Road, Walsall, WS5 4QL
            &nbsp;&middot;&nbsp; Phone: 01922 351933, 07777 138 166<br>
            www.insighthealthservices.co.uk
            &nbsp;&middot;&nbsp; bookings@insighthealthservices.co.uk
        </div>
    </div>
</body>
</html>
