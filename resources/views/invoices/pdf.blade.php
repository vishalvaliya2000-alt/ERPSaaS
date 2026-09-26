@php
    $tenant = $tenant ?? ($invoice->tenant ?? \App\Services\TenantManager::getTenant());
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 20px 25px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.35;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .box-table {
            border: 1px solid #1a1a1a;
            margin-top: 10px;
        }
        .box-table td, .box-table th {
            border: 1px solid #1a1a1a;
            padding: 5px 7px;
            vertical-align: top;
        }
        .items-table {
            border: 1px solid #1a1a1a;
            margin-top: 10px;
        }
        .items-table th {
            background-color: #091315;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            padding: 6px 7px;
            border: 1px solid #091315;
            text-align: left;
        }
        .items-table td {
            border-left: 1px solid #1a1a1a;
            border-right: 1px solid #1a1a1a;
            padding: 6px 7px;
        }
        .items-table tr.total-row td {
            border-top: 1px solid #1a1a1a;
            border-bottom: 1px solid #1a1a1a;
            background-color: #f9f9f9;
        }
        .badge {
            background-color: #091315;
            color: #D7FF53;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
            border-radius: 4px;
        }
        .footer-note {
            font-size: 9px;
            color: #666;
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <!-- Top Header -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <h1 style="margin: 0; font-size: 20px; font-weight: 900; color: #091315; text-transform: uppercase; letter-spacing: -0.5px;">
                    {{ $tenant->name ?? config('app.name', 'ERPSaaS') }}
                </h1>
                <p style="margin: 2px 0 0 0; font-size: 10px; color: #555;">
                    {{ $tenant->tagline ?? 'Tax Invoice & Commercial Accounts' }}
                </p>
                <p style="margin: 4px 0 0 0; font-size: 10px; color: #333;">
                    {{ $tenant->full_address ?? ($tenant->address ?? 'Registered Office') }}<br>
                    <b>GSTIN:</b> {{ $tenant->tax_id_number ?? 'N/A' }} | <b>State:</b> {{ $tenant->state ?? 'Gujarat' }}<br>
                    <b>Email:</b> {{ $tenant->email ?? '' }} | <b>Phone:</b> {{ $tenant->phone ?? '' }}
                </p>
            </td>
            <td style="width: 40%;" class="text-right">
                <span class="badge">TAX INVOICE</span>
                <p style="margin: 6px 0 0 0; font-size: 14px; font-weight: bold; color: #091315;">
                    {{ $invoice->invoice_number }}
                </p>
                <p style="margin: 2px 0 0 0; font-size: 10px; color: #333;">
                    <b>Invoice Date:</b> {{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : date('d M Y') }}<br>
                    <b>Due Date:</b> {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : date('d M Y', strtotime('+30 days')) }}<br>
                    <b>Status:</b> <span class="uppercase font-bold">{{ $invoice->status }}</span>
                </p>
            </td>
        </tr>
    </table>

    <!-- Buyer & Shipment Reference Details Box -->
    <table class="box-table">
        <tr>
            <td style="width: 50%;">
                <span style="font-size: 9px; text-transform: uppercase; font-weight: bold; color: #666;">Billed To / Buyer (Bill to & Ship to)</span>
                <p style="margin: 3px 0 0 0; font-size: 12px; font-weight: bold; color: #091315;">
                    {{ $invoice->customer?->company_name ?? 'Valued Customer' }}
                </p>
                <p style="margin: 2px 0 0 0; font-size: 10px; color: #333;">
                    {{ $invoice->customer?->address_line ?? 'Client Factory Godown' }}<br>
                    {{ $invoice->customer?->city ?? 'City' }}, {{ $invoice->customer?->state ?? 'State' }} - {{ $invoice->customer?->pincode ?? '' }}<br>
                    <b>GSTIN:</b> {{ $invoice->customer?->gst_number ?? 'URP / Not Provided' }}<br>
                    <b>Contact:</b> {{ $invoice->customer?->primary_contact_person ?? 'Purchasing Manager' }} ({{ $invoice->customer?->primary_phone ?? '' }})
                </p>
            </td>
            <td style="width: 50%;">
                <span style="font-size: 9px; text-transform: uppercase; font-weight: bold; color: #666;">Order & Dispatch Details</span>
                <table style="width: 100%; margin-top: 3px; font-size: 10px;">
                    <tr>
                        <td style="border: none; padding: 1px 0; color: #666; width: 45%;"><b>Buyer PO No(s):</b></td>
                        <td style="border: none; padding: 1px 0; font-weight: bold; color: #091315;">
                            {{ $invoice->display_po_numbers ?? ($invoice->salesOrder?->order_number ?? 'PO-Direct') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 1px 0; color: #666;"><b>Transport / LR No:</b></td>
                        <td style="border: none; padding: 1px 0; font-weight: bold; color: #091315;">
                            {{ $invoice->effective_shipment?->lr_number ?? ($invoice->commercialShipment?->lr_number ?? '—') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 1px 0; color: #666;"><b>Transporter:</b></td>
                        <td style="border: none; padding: 1px 0;">
                            {{ $invoice->effective_shipment?->transporter ?? ($invoice->commercialShipment?->transporter ?? 'Road Transport') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 1px 0; color: #666;"><b>Vehicle No:</b></td>
                        <td style="border: none; padding: 1px 0;">
                            {{ $invoice->effective_shipment?->vehicle_number ?? ($invoice->commercialShipment?->vehicle_number ?? '—') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 1px 0; color: #666;"><b>Payment Terms:</b></td>
                        <td style="border: none; padding: 1px 0;">
                            {{ $invoice->customer?->payment_terms_days ? $invoice->customer->payment_terms_days . ' Days Credit' : '30 Days Credit' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Description of Goods</th>
                <th style="width: 12%;" class="text-center">HSN/SAC</th>
                <th style="width: 13%;" class="text-right">Quantity (KG)</th>
                <th style="width: 12%;" class="text-right">Rate (₹/KG)</th>
                <th style="width: 13%;" class="text-right">Taxable Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @php $idx = 1; $totalWeight = 0; @endphp
            @forelse($invoice->items as $item)
                @php 
                    $qty = (float)$item->quantity; 
                    $totalWeight += $qty;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx++ }}</td>
                    <td>
                        <b>{{ $item->product?->product_name ?? 'Dehydrated Material' }}</b>
                        @if($item->salesOrder)
                            <div style="font-size: 9px; color: #666;">Against Order: {{ $item->salesOrder->order_number }}</div>
                        @endif
                    </td>
                    <td class="text-center font-bold">{{ $item->product?->hsn_code ?? '07122000' }}</td>
                    <td class="text-right font-bold">{{ number_format($qty, 2) }}</td>
                    <td class="text-right">₹{{ number_format((float)$item->rate, 2) }}</td>
                    <td class="text-right font-bold">₹{{ number_format((float)$item->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-center">1</td>
                    <td>
                        <b>Dehydrated Food Ingredients & Agricultural Commodities</b>
                        @if($invoice->salesOrder)
                            <div style="font-size: 9px; color: #666;">Against Contract: {{ $invoice->salesOrder->order_number }}</div>
                        @endif
                    </td>
                    <td class="text-center font-bold">07122000</td>
                    <td class="text-right font-bold">—</td>
                    <td class="text-right">—</td>
                    <td class="text-right font-bold">₹{{ number_format((float)$invoice->material_subtotal, 2) }}</td>
                </tr>
            @endforelse

            <!-- Fill blank space rows if needed -->
            @for($i = count($invoice->items); $i < 3; $i++)
                <tr>
                    <td style="color: transparent;">-</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            @endfor

            <!-- Subtotal & Calculations -->
            <tr class="total-row">
                <td colspan="3" class="font-bold text-right">Material Subtotal:</td>
                <td class="text-right font-bold">{{ $totalWeight > 0 ? number_format($totalWeight, 2) . ' KG' : '' }}</td>
                <td></td>
                <td class="text-right font-bold">₹{{ number_format((float)$invoice->material_subtotal, 2) }}</td>
            </tr>

            @if((float)$invoice->freight_amount > 0)
                <tr class="total-row">
                    <td colspan="5" class="text-right font-bold">Freight / Transportation Charges:</td>
                    <td class="text-right font-bold">₹{{ number_format((float)$invoice->freight_amount, 2) }}</td>
                </tr>
            @endif

            <tr class="total-row">
                <td colspan="5" class="text-right font-bold">Taxable Amount:</td>
                <td class="text-right font-bold">₹{{ number_format((float)$invoice->subtotal, 2) }}</td>
            </tr>

            <!-- GST Calculation (Interstate IGST vs Intrastate CGST+SGST) -->
            @php
                $custState = strtolower($invoice->customer?->state ?? '');
                $isInterstate = !empty($custState) && !str_contains($custState, 'gujarat');
                $gstRate = (float)$invoice->subtotal > 0 ? round(((float)$invoice->gst_amount / (float)$invoice->subtotal) * 100, 1) : 5.0;
            @endphp

            @if($isInterstate)
                <tr class="total-row">
                    <td colspan="5" class="text-right">Integrated GST (IGST @ {{ $gstRate }}%):</td>
                    <td class="text-right font-bold">₹{{ number_format((float)$invoice->gst_amount, 2) }}</td>
                </tr>
            @else
                <tr class="total-row">
                    <td colspan="5" class="text-right">Central GST (CGST @ {{ $gstRate / 2 }}%):</td>
                    <td class="text-right font-bold">₹{{ number_format((float)$invoice->gst_amount / 2, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="5" class="text-right">State GST (SGST @ {{ $gstRate / 2 }}%):</td>
                    <td class="text-right font-bold">₹{{ number_format((float)$invoice->gst_amount / 2, 2) }}</td>
                </tr>
            @endif

            <tr class="total-row" style="background-color: #091315; color: #ffffff;">
                <td colspan="5" class="font-bold text-right" style="color: #ffffff; font-size: 11px;">
                    GRAND TOTAL (INR):
                </td>
                <td class="text-right font-bold" style="color: #D7FF53; font-size: 13px;">
                    ₹{{ number_format((float)$invoice->total_amount, 2) }}
                </td>
            </tr>

            @if((float)$invoice->amount_received > 0)
                <tr class="total-row" style="background-color: #f0fdf4;">
                    <td colspan="5" class="text-right font-bold" style="color: #166534; font-size: 10px;">
                        Less Advance Received / Paid:
                    </td>
                    <td class="text-right font-bold" style="color: #166534; font-size: 11px;">
                        -₹{{ number_format((float)$invoice->amount_received, 2) }}
                    </td>
                </tr>
                <tr class="total-row" style="background-color: #1e293b; color: #ffffff;">
                    <td colspan="5" class="font-bold text-right" style="color: #ffffff; font-size: 11px;">
                        NET BALANCE PAYABLE (INR):
                    </td>
                    <td class="text-right font-bold" style="color: #D7FF53; font-size: 13px;">
                        ₹{{ number_format((float)$invoice->balance_due, 2) }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Bank Details & Authorized Signatory -->
    <table class="box-table" style="margin-top: 10px;">
        <tr>
            <td style="width: 60%;">
                <span style="font-size: 9px; text-transform: uppercase; font-weight: bold; color: #666;">Bank & Settlement Details for RTGS / NEFT</span>
                <p style="margin: 3px 0 0 0; font-size: 10px; color: #1a1a1a;">
                    <b>Bank Name:</b> {{ $tenant->bank_name ?: 'HDFC Bank Limited' }}<br>
                    <b>Branch:</b> {{ $tenant->bank_branch ?: 'Main Branch' }}<br>
                    <b>Account Name:</b> {{ $tenant->name ?? config('app.name', 'ERPSaaS') }}<br>
                    <b>Account Number:</b> {{ $tenant->bank_account_number ?: '50200012345678' }}<br>
                    <b>IFSC Code:</b> {{ $tenant->bank_ifsc_code ?: 'HDFC0000123' }}
                </p>
                @if($invoice->notes)
                    <p style="margin: 4px 0 0 0; font-size: 9px; color: #555; border-top: 1px dashed #ccc; padding-top: 3px;">
                        <b>Remarks:</b> {{ $invoice->notes }}
                    </p>
                @endif
            </td>
            <td style="width: 40%;" class="text-center">
                <p style="margin: 0; font-size: 10px; font-weight: bold; color: #091315;">
                    For, {{ $tenant->name ?? config('app.name', 'ERPSaaS') }}
                </p>
                <div style="height: 45px;"></div>
                <p style="margin: 0; font-size: 9px; color: #666; border-top: 1px solid #999; padding-top: 3px;">
                    Authorized Signatory
                </p>
            </td>
        </tr>
    </table>

    <!-- Footer Disclaimers -->
    <div class="footer-note">
        <b>Terms & Conditions:</b> 1. Goods once sold will not be taken back or exchanged. 2. Interest @ 18% p.a. will be charged if payment is not made within credit term days. 3. Subject to Mahuva (Gujarat) jurisdiction only. 4. This is an official ERP generated invoice document.
    </div>

</body>
</html>
