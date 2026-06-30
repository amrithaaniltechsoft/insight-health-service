<?php

namespace App\Http\Controllers;

use Dompdf\Dompdf;

class InvoiceController extends Controller
{
    public function preview($id)
    {
        $logoPath = public_path('storage/logo.png');
        $logoSrc = file_exists($logoPath) ? $logoPath : null;

        // TODO: Replace with actual DB query when invoice table exists
        // $invoice = \App\Models\Invoice::with('items')->findOrFail($id);
        $invoice = (object) [
            'submitted_date' => '2026-05-05',
            'book_ref' => 'INV20260505001',
            'inv_due' => '2026-06-10',
            'arrival_date' => '2026-05-10',
            'operator_name' => 'John Travels LLC',
            'operator_adress' => 'Dubai, UAE',
            'operator_country_code' => '+971',
            'operator_phone' => '50 123 4567',
            'client_name' => 'Sarah Ahmed',
            'sub_total' => 1500.00,
            'vat_percent' => 5,
            'vat_amount' => 75.00,
            'total' => 1575.00,
        ];

        $invoice_items = [
            (object) ['description' => 'Desert Safari Tour - 4 Pax', 'qty' => 4, 'unit_price' => 250.00, 'total_price' => 1000.00],
            (object) ['description' => 'Dhow Cruise Dinner', 'qty' => 2, 'unit_price' => 150.00, 'total_price' => 300.00],
            (object) ['description' => 'Airport Transfer', 'qty' => 2, 'unit_price' => 100.00, 'total_price' => 200.00],
        ];

        $amount = $invoice->total;
        $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $amountInWords = ucfirst($formatter->format($amount));

        $html = '
        <html>
        <head>
            <style>
                * { font-family: DejaVu Sans, sans-serif !important; }
                body { font-family: DejaVu Sans; font-size:12px; }
                table { width:100%; border-collapse:collapse; }
                td { vertical-align:top; padding:3px; }
                .right { text-align:right; }
                .bold { font-weight:bold; }
                .item-row td { padding:5px 3px; }
                .bg{background-color:#f5f5f5;}
            </style>
        </head>
        <body>

        <table style="border-top:5px solid #212f90">
            <tr>
                <td width="60%" style="color:#454545;padding-top:20px;">
                    <div class="bold" style="color:#04b7e1;font-size: 25px;font-weight: 100;">MAGIC SANDS LLC</div>
                    Shop #04, Building # 1//2210, Block # 415,<br>
                    Al wafa street, Amerat, Oman<br>
                    E: finance@magicsandsdmc.com<br>
                    M: +968 9677 2959<br>
                    <span style="font-weight: 600">CR : 1533476 | VAT: OM1100390863 | TAX: 212786</span>
                </td>
                <td width="40%" class="right" style="padding-top:10px;">
                    '.($logoSrc ? '<img src="'.$logoSrc.'" height="100">' : '').'<br><br>
                </td>
            </tr>
        </table>

        <br>

        <div>
            <div style="font-size:30px;color:#212f90" class="bold">TAX INVOICE</div>
            <span style="color:#e60909;font-weight: 200;font-size:15px">Submitted '.date('d-M-Y',strtotime($invoice->submitted_date)).'</span>
        </div>

        <br>

        <table>
            <tr style="border-bottom:1px solid #D3D3D3">
                <td width="40%">
                   <div style="font-size: 13px;font-weight:bold;color:#454545">Invoice for</div>
                   <br>
                   <div style="color:#454545">'.$invoice->operator_name.'<br>'.$invoice->operator_adress.'<br>'.$invoice->operator_country_code.' '.$invoice->operator_phone.'</div>
                </td>
                <td width="33%">
                    <div><span style="font-size: 13px;font-weight:bold;color:#454545">Payable to</span></div>
                    <div style="color:#454545">MAGIC SANDS LLC</div>
                    <br>
                    <div style="font-size: 13px;font-weight:bold;color:#454545">Client Name</div>
                    <div style="color:#454545">'.$invoice->client_name.'</div>
                </td>
                <td width="33%" style="text-align:right;">
                    <table width="100%">
                        <tr>
                            <td width="50%" style="font-weight:bold; font-size:13px; color:#454545;">Invoice #</td>
                            <td width="50%" style="text-align:right; color:#454545;">'.$invoice->book_ref.'</td>
                        </tr>
                        <tr>
                            <td style="font-weight:bold; font-size:13px; color:#454545;">Inv Due</td>
                            <td style="text-align:right; color:#454545;">'.date('d-M-Y',strtotime($invoice->inv_due)).'</td>
                        </tr>
                        <tr>
                            <td style="font-weight:bold; font-size:13px; color:#454545;">Arrival Date</td>
                            <td style="text-align:right; color:#454545;">'.date('d-M-Y',strtotime($invoice->arrival_date)).'</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <br>

        <table>
            <tr>
                <td style="font-weight:bold; font-size:13px; color:#212f90;line-height:2;">Description</td>
                <td style="font-weight:bold; font-size:13px; color:#212f90;line-height:2;text-align:center;" width="30">Qty</td>
                <td style="font-weight:bold; font-size:13px; color:#212f90;line-height:2;" width="100" class="right">Unit price</td>
                <td style="font-weight:bold; font-size:13px; color:#212f90;line-height:2;" width="120" class="right">Total price</td>
            </tr>';
            $i = 1;
            foreach($invoice_items as $inv_items){
                $rowClass = ($i % 2 != 0) ? 'bg' : '';
                $html .= '
            <tr class="item-row '.$rowClass.'" style="color:#454545;">
                <td>'.$inv_items->description.'</td>
                <td style="text-align:center;">'.$inv_items->qty.'</td>
                <td class="right">$'.number_format($inv_items->unit_price,2).'</td>
                <td class="right">$'.number_format($inv_items->total_price,2).'</td>
            </tr>';
            $i++;
        }
        $html .= '
        </table>

        <br>

        <table style="border-bottom:1px solid #D3D3D3">
            <tr style="color:#454545;">
                <td>TOTAL CHARGES IN USD</td>
                <td style="text-align:right;">$'.number_format($invoice->total,2).'</td>
            </tr>
        </table>

        <br>

        <table>
            <tr>
                <td width="60%">
                    <div style="color:#454545;margin-bottom:10px;">'.$amountInWords.'</div>
                    <div style="color:#212f90; font-weight:bold; margin-bottom:5px;">BANK DETAILS</div>
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td width="100" style="color:#212f90;padding:0; line-height:1;font-weight:bold;">Bank Name</td>
                            <td width="15" style="color:#212f90;padding:0; line-height:1;">:</td>
                            <td style="color:#212f90;padding:0; line-height:1;">Bank Muscat</td>
                        </tr>
                        <tr>
                            <td style="color:#212f90;padding:0; line-height:1;">A/c Name</td>
                            <td style="color:#212f90;padding:0; line-height:1;">:</td>
                            <td style="color:#212f90;padding:0; line-height:1;">MAGIC SANDS LLC</td>
                        </tr>
                        <tr>
                            <td style="color:#212f90;padding:0; line-height:1;">A/c Number</td>
                            <td style="color:#212f90;padding:0; line-height:1;">:</td>
                            <td style="color:#212f90;padding:0; line-height:1;">0315075764290017</td>
                        </tr>
                        <tr>
                            <td style="color:#212f90;padding:0; line-height:1;">IBAN</td>
                            <td style="color:#212f90;padding:0; line-height:1;">:</td>
                            <td style="color:#212f90;padding:0; line-height:1;">OM130270315075764290017</td>
                        </tr>
                        <tr>
                            <td style="color:#212f90;padding:0; line-height:1;">Currency</td>
                            <td style="color:#212f90;padding:0; line-height:1;">:</td>
                            <td style="color:#212f90;padding:0; line-height:1;">OMR</td>
                        </tr>
                        <tr>
                            <td style="color:#212f90;padding:0; line-height:1;">Address</td>
                            <td style="color:#212f90;padding:0; line-height:1;">:</td>
                            <td style="color:#212f90;padding:0; line-height:1;">MSQ, Muscat</td>
                        </tr>
                    </table>
                </td>
                <td width="20%">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="width:60%; color:#212f90;">Subtotal</td>
                            <td style="width:40%; color:#212f90; text-align:right;">$'.number_format($invoice->sub_total,2).'</td>
                        </tr>
                        <tr>
                            <td style="color:#212f90;">VAT(%)</td>
                            <td style="color:#212f90; text-align:right;">'.$invoice->vat_percent.'%</td>
                        </tr>
                        <tr>
                            <td style="color:#212f90;">VAT(Amount)</td>
                            <td style="color:#212f90; text-align:right;">$'.number_format($invoice->vat_amount,2).'</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:center; font-weight:bold; color:#e60909; font-size:23px;">
                                $'.number_format($invoice->total,2).'
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <br><br>
        Thank you for your business !

        </body>
        </html>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return response($dompdf->output(), 200, ['Content-Type' => 'application/pdf']);
    }
}
