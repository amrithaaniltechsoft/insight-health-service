<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $v['title'] ?: 'ANOMALY ULTRASOUND SCAN' }}</title>
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
        padding: 3px 6px;
        vertical-align: middle;
        font-size: 10px;
    }
    td.lbl {
        background-color: #e6deb4;
        font-weight: bold;
        white-space: nowrap;
    }
    td.lbl-white {
        background-color: #fff;
        font-weight: bold;
        white-space: nowrap;
    }
    .section-title {
        font-weight: bold;
        font-size: 10.5px;
        margin: 6px 0 2px 0;
    }
    .layout-table {
        width: 100%;
        border-collapse: collapse;
        border: none;
    }
    .layout-table td {
        border: none;
        padding: 0;
        vertical-align: top;
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
        margin-top: 6px;
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
        @include('reports.partials.header')

        <div class="doc-title">{{ $v['title'] ?: 'ANOMALY ULTRASOUND SCAN' }}</div>

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
                    <td class="lbl">Contact No</td>
                    <td>{{ $v['contact_no'] }}</td>
                    <td class="lbl">Study Date</td>
                    <td>{{ $v['study_date'] }}</td>
                    <td class="lbl">Consent to Scan</td>
                    <td>{{ $v['consent'] }}</td>
                </tr>
                <tr>
                    <td class="lbl">Gestation</td>
                    <td colspan="2">{{ $v['gestation'] }}</td>
                    <td class="lbl" colspan="2" style="text-align: center;">Estimated Date of Delivery</td>
                    <td>{{ $v['edd'] }}</td>
                </tr>
            </tbody>
        </table>

        <div style="padding: 6px 8px 4px 8px;">
            {{-- Primary findings & Gender --}}
            <table class="layout-table">
                <tr>
                    <td style="width: 48%;">
                        <table class="grid">
                            <colgroup><col style="width: 58%;"><col style="width: 42%;"></colgroup>
                            <tr>
                                <td class="lbl-white">FETAL HEART BEAT</td>
                                <td>{{ $v['fetal_heart_beat'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">PLACENTA POSITION</td>
                                <td>{{ $v['placenta_position'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">PLACENTA SITE</td>
                                <td>{{ $v['placenta_site'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">AMNIOTIC FLUID</td>
                                <td>{{ $v['amniotic_fluid'] }}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 4%;"></td>
                    <td style="width: 48%;">
                        <table class="grid">
                            <colgroup><col style="width: 60%;"><col style="width: 40%;"></colgroup>
                            <tr>
                                <td class="lbl" style="text-align: center;">FETAL GENDER REQUESTED</td>
                                <td>{{ $v['gender_requested'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white" style="text-align: center;">FETAL GENDER</td>
                                <td>{{ $v['fetal_gender'] }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- Biometry Measurements --}}
            <div class="section-title">Biometry Measurements</div>
            <table class="layout-table">
                <tr>
                    <td style="width: 62%;">
                        <table class="grid">
                            <colgroup><col style="width: 73%;"><col style="width: 18%;"><col style="width: 9%;"></colgroup>
                            <tr>
                                <td class="lbl-white">Head Circumference (HC)</td>
                                <td style="text-align: center;">{{ $v['hc'] }}</td>
                                <td style="border: none; padding-left: 4px;">mm</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Abdominal Circumference (AC)</td>
                                <td style="text-align: center;">{{ $v['ac'] }}</td>
                                <td style="border: none; padding-left: 4px;">mm</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Femur Length (FL)</td>
                                <td style="text-align: center;">{{ $v['fl'] }}</td>
                                <td style="border: none; padding-left: 4px;">mm</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Cerebellum (CER)</td>
                                <td style="text-align: center;">{{ $v['cer'] }}</td>
                                <td style="border: none; padding-left: 4px;">mm</td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 38%;"></td>
                </tr>
            </table>

            {{-- Anatomy Sections: Left Column and Right Column --}}
            <table class="layout-table" style="margin-top: 4px;">
                <tr>
                    {{-- Left Column: CNS, Face/Lips, Chest --}}
                    <td style="width: 48%;">
                        <div class="section-title">CNS - Head and Neck</div>
                        <table class="grid">
                            <colgroup><col style="width: 62%;"><col style="width: 28%;"><col style="width: 10%;"></colgroup>
                            <tr>
                                <td class="lbl-white">Skull</td>
                                <td style="text-align: center;" colspan="2">{{ $v['skull'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Atrium</td>
                                <td style="text-align: center;">{{ $v['atrium'] }}</td>
                                <td style="border: none; padding-left: 4px;">mm</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Brain</td>
                                <td style="text-align: center;" colspan="2">{{ $v['brain_cns'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Nuchal</td>
                                <td style="text-align: center;" colspan="2">{{ $v['nuchal'] }}</td>
                            </tr>
                        </table>

                        <div style="margin-top: 8px;">
                            <table class="layout-table">
                                <tr>
                                    <td style="font-weight: bold; width: 40%; font-size: 10.5px; padding-top: 3px;">Face / Lips</td>
                                    <td style="width: 60%;">
                                        <table class="grid">
                                            <tr><td style="text-align: center;">{{ $v['face_lips'] }}</td></tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="section-title" style="margin-top: 8px;">Chest</div>
                        <table class="grid">
                            <colgroup><col style="width: 50%;"><col style="width: 50%;"></colgroup>
                            <tr>
                                <td class="lbl-white">Heart 4CH</td>
                                <td style="text-align: center;">{{ $v['heart_4ch'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Situs</td>
                                <td style="text-align: center;">{{ $v['situs'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">LVOT</td>
                                <td style="text-align: center;">{{ $v['lvot'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">RVOT</td>
                                <td style="text-align: center;">{{ $v['rvot'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">3VT View</td>
                                <td style="text-align: center;">{{ $v['three_vt_view'] }}</td>
                            </tr>
                        </table>
                    </td>

                    <td style="width: 4%;"></td>

                    {{-- Right Column: Abdomen, Spine, Extremities --}}
                    <td style="width: 48%;">
                        <div class="section-title">Abdomen</div>
                        <table class="grid">
                            <colgroup><col style="width: 50%;"><col style="width: 50%;"></colgroup>
                            <tr>
                                <td class="lbl-white">Stomach</td>
                                <td style="text-align: center;">{{ $v['stomach'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Abdominal Wall</td>
                                <td style="text-align: center;">{{ $v['abdominal_wall'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Bowel</td>
                                <td style="text-align: center;">{{ $v['bowel'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Kidneys</td>
                                <td style="text-align: center;">{{ $v['kidneys_anatomy'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Bladder</td>
                                <td style="text-align: center;">{{ $v['bladder'] }}</td>
                            </tr>
                        </table>

                        <div class="section-title" style="margin-top: 8px;">Spine</div>
                        <table class="grid">
                            <colgroup><col style="width: 50%;"><col style="width: 50%;"></colgroup>
                            <tr>
                                <td class="lbl-white">Saggital</td>
                                <td style="text-align: center;">{{ $v['spine_sagittal'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Transverse</td>
                                <td style="text-align: center;">{{ $v['spine_transverse'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Coronal</td>
                                <td style="text-align: center;">{{ $v['spine_coronal'] }}</td>
                            </tr>
                        </table>

                        <div class="section-title" style="margin-top: 8px;">Extremities</div>
                        <table class="grid">
                            <colgroup><col style="width: 50%;"><col style="width: 50%;"></colgroup>
                            <tr>
                                <td class="lbl-white">Femur</td>
                                <td style="text-align: center;">{{ $v['femur_anatomy'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Both Hands</td>
                                <td style="text-align: center;">{{ $v['both_hands'] }}</td>
                            </tr>
                            <tr>
                                <td class="lbl-white">Both Feet</td>
                                <td style="text-align: center;">{{ $v['both_feet'] }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- Scan Complete --}}
            <div style="margin-top: 6px; margin-bottom: 6px;">
                <table class="layout-table" style="width: 48%;">
                    <tr>
                        <td style="font-weight: bold; width: 45%; font-size: 11px; padding-top: 3px;">Scan Complete :</td>
                        <td style="width: 55%;">
                            <table class="grid">
                                <tr><td style="text-align: center;">{{ $v['scan_complete'] }}</td></tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Comments --}}
            <table class="grid" style="margin-top: 6px;">
                <tr>
                    <td class="lbl" style="width: 14%; text-align: center; vertical-align: middle; font-weight: bold; font-size: 11px; padding: 10px 6px;">Comments</td>
                    <td style="width: 86%; vertical-align: middle; padding: 8px 10px; font-size: 10.5px; line-height: 1.45; background-color: #fff;">
                        {{ $v['comments'] }}
                    </td>
                </tr>
            </table>

            {{-- Signatures --}}
            <table class="grid" style="margin-top: 6px;">
                <colgroup>
                    <col style="width: 14%;">
                    <col style="width: 36%;">
                    <col style="width: 14%;">
                    <col style="width: 36%;">
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

        {{-- Footer Band --}}
        @include('reports.partials.footer')
    </div>
</body>
</html>