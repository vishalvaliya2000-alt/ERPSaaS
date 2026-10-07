<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $note->type_label }} — {{ $note->note_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            @page { size: A4; margin: 12mm; }
        }
    </style>
</head>
<body class="bg-white text-neutral-900 font-sans p-6 max-w-4xl mx-auto text-xs">
    <!-- Print Action Bar -->
    <div class="no-print mb-6 p-4 bg-neutral-100 rounded-2xl flex items-center justify-between border border-neutral-200">
        <div class="flex items-center gap-2">
            <span class="font-bold text-sm">Official Commercial Voucher</span>
            <span class="text-neutral-500">• {{ $note->note_number }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-5 py-2 bg-neutral-900 text-white font-bold text-xs rounded-xl hover:bg-black cursor-pointer shadow-sm">
                🖨️ Print Voucher
            </button>
            <button onclick="window.close()" class="px-4 py-2 bg-white text-neutral-700 font-bold text-xs rounded-xl border border-neutral-300 hover:bg-neutral-50 cursor-pointer">
                Close
            </button>
        </div>
    </div>

    <!-- Voucher Border Container -->
    <div class="border-2 border-neutral-900 rounded-xl p-6 space-y-5">
        <!-- Header -->
        <div class="flex justify-between items-start border-b-2 border-neutral-900 pb-5">
            <div class="space-y-1">
                <h1 class="text-xl font-black uppercase tracking-tight text-neutral-900 font-serif">
                    {{ $tenant->name ?? 'ERPSaaS Enterprises' }}
                </h1>
                <p class="text-neutral-600 leading-tight">
                    {{ $tenant->address_line ?? '' }}<br>
                    {{ $tenant->city ?? '' }}{{ $tenant->state ? ', ' . $tenant->state : '' }} {{ $tenant->pincode ? '— ' . $tenant->pincode : '' }}
                </p>
                <div class="pt-1 font-mono text-[11px] text-neutral-700 space-y-0.5">
                    @if($tenant->tax_id_number)
                    <div>GSTIN: <b>{{ $tenant->tax_id_number }}</b></div>
                    @endif
                    @if($tenant->pan_number)
                    <div>PAN: <b>{{ $tenant->pan_number }}</b></div>
                    @endif
                </div>
            </div>

            <div class="text-right space-y-1">
                <div class="inline-block px-3 py-1 bg-neutral-900 text-white font-black text-sm uppercase tracking-wider rounded">
                    {{ $note->type_label }}
                </div>
                <div class="text-[10px] text-neutral-500 font-bold uppercase tracking-wider">
                    {{ $note->is_credit_note ? 'Issued Under Section 34 of CGST Act' : 'Supplementary Invoice / Debit Note' }}
                </div>
                <div class="font-mono pt-2 text-neutral-800">
                    <div><b>Note No:</b> {{ $note->note_number }}</div>
                    <div><b>Date:</b> {{ $note->note_date ? $note->note_date->format('d/m/Y') : '—' }}</div>
                </div>
            </div>
        </div>

        <!-- 2-Column Party & Invoice Details -->
        <div class="grid grid-cols-2 gap-4 border-b border-neutral-300 pb-4">
            <!-- Customer (Bill To) -->
            <div class="space-y-1 border-r border-neutral-200 pr-4">
                <span class="font-black text-[10px] text-neutral-400 uppercase tracking-wider block">Details of Receiver (Billed To):</span>
                <div class="font-bold text-sm text-neutral-900">{{ $note->customer?->company_name }}</div>
                <p class="text-neutral-600 leading-relaxed text-[11px]">
                    {{ $note->customer?->billing_address ?? ($note->customer?->city . ', ' . $note->customer?->state) }}
                </p>
                <div class="font-mono text-[11px] pt-1 text-neutral-700 space-y-0.5">
                    @if($note->customer?->gst_number)
                    <div>GSTIN / UIN: <b>{{ $note->customer?->gst_number }}</b></div>
                    @endif
                    <div>State & Code: <b>{{ $note->customer?->state ?? 'Standard' }}</b></div>
                </div>
            </div>

            <!-- Original Invoice Reference -->
            <div class="space-y-1 pl-2">
                <span class="font-black text-[10px] text-neutral-400 uppercase tracking-wider block">Reference & Purpose:</span>
                <div class="font-mono text-[11px] space-y-1 text-neutral-800">
                    <div>Original Invoice No: <b>{{ $note->original_invoice_number ?: 'N/A' }}</b></div>
                    @if($note->invoice && $note->invoice->invoice_date)
                    <div>Original Invoice Date: <b>{{ $note->invoice->invoice_date->format('d/m/Y') }}</b></div>
                    @endif
                    <div>Original Invoice Value: <b>₹{{ $note->invoice ? number_format($note->invoice->total_amount, 2) : '—' }}</b></div>
                    <div class="pt-1.5 font-sans">
                        <span class="text-neutral-500">Reason:</span>
                        <div class="font-bold text-neutral-900">{{ $note->reason }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <table class="w-full text-left border-collapse border border-neutral-300 text-[11px]">
            <thead>
                <tr class="bg-neutral-100 font-bold border-b border-neutral-300 text-neutral-800">
                    <th class="p-2 border-r border-neutral-300 text-center w-8">#</th>
                    <th class="p-2 border-r border-neutral-300">Description of Goods / Services</th>
                    <th class="p-2 border-r border-neutral-300 w-20 text-center">HSN/SAC</th>
                    <th class="p-2 border-r border-neutral-300 w-20 text-right">Quantity</th>
                    <th class="p-2 border-r border-neutral-300 w-20 text-right">Rate (₹)</th>
                    <th class="p-2 border-r border-neutral-300 w-24 text-right">Taxable Value</th>
                    <th class="p-2 border-r border-neutral-300 w-16 text-center">GST %</th>
                    <th class="p-2 w-24 text-right">Total (₹)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200">
                @foreach($note->items as $idx => $it)
                <tr>
                    <td class="p-2 border-r border-neutral-300 text-center font-mono">{{ $idx + 1 }}</td>
                    <td class="p-2 border-r border-neutral-300 font-medium">{{ $it->description }}</td>
                    <td class="p-2 border-r border-neutral-300 text-center font-mono">{{ $it->hsn_code }}</td>
                    <td class="p-2 border-r border-neutral-300 text-right font-mono">{{ number_format($it->quantity, 2) }} {{ $it->uom }}</td>
                    <td class="p-2 border-r border-neutral-300 text-right font-mono">{{ number_format($it->rate, 2) }}</td>
                    <td class="p-2 border-r border-neutral-300 text-right font-mono">{{ number_format($it->subtotal, 2) }}</td>
                    <td class="p-2 border-r border-neutral-300 text-center font-mono">{{ number_format($it->tax_rate_percent, 0) }}%</td>
                    <td class="p-2 text-right font-mono font-bold">{{ number_format($it->total_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-neutral-900 bg-neutral-50 font-bold">
                    <td colspan="5" class="p-2 text-right border-r border-neutral-300">Subtotal:</td>
                    <td class="p-2 text-right font-mono border-r border-neutral-300">₹{{ number_format($note->subtotal, 2) }}</td>
                    <td class="p-2 border-r border-neutral-300"></td>
                    <td class="p-2 text-right font-mono">₹{{ number_format($note->subtotal, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Tax Breakdown & Grand Total -->
        <div class="flex justify-between items-start pt-2">
            <!-- Left Notes -->
            <div class="max-w-md space-y-2 text-[10px] text-neutral-600">
                @if($note->notes)
                <div class="p-2 bg-neutral-50 border border-neutral-200 rounded">
                    <b>Remarks:</b> {{ $note->notes }}
                </div>
                @endif
                <p>
                    <b>Declaration:</b> We declare that this {{ strtolower($note->type_label) }} shows the actual price of the goods/services described and that all particulars are true and correct.
                </p>
            </div>

            <!-- Right Tax Summary -->
            <div class="w-64 border border-neutral-300 rounded p-3 space-y-1.5 font-mono text-[11px] bg-neutral-50">
                <div class="flex justify-between">
                    <span>Taxable Amount:</span>
                    <span>₹{{ number_format($note->subtotal, 2) }}</span>
                </div>

                @if($note->is_interstate)
                <div class="flex justify-between">
                    <span>IGST Amount:</span>
                    <span>₹{{ number_format($note->igst_amount ?: $note->tax_amount, 2) }}</span>
                </div>
                @else
                <div class="flex justify-between">
                    <span>CGST Amount:</span>
                    <span>₹{{ number_format($note->cgst_amount, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>SGST Amount:</span>
                    <span>₹{{ number_format($note->sgst_amount, 2) }}</span>
                </div>
                @endif

                <div class="border-t border-neutral-300 pt-1.5 flex justify-between font-black text-xs text-neutral-900">
                    <span>Net Total:</span>
                    <span>₹{{ number_format($note->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Authorized Signature Section -->
        <div class="flex justify-between items-end pt-12 border-t border-neutral-200 text-xs">
            <div class="text-[10px] text-neutral-500 space-y-0.5">
                <div>Generated through ERPSaaS Platform</div>
                <div>Status: {{ $note->status }} • Voucher ID: #{{ $note->id }}</div>
            </div>

            <div class="text-right space-y-12">
                <div class="font-bold text-neutral-900">For {{ $tenant->name ?? 'ERPSaaS Enterprises' }}</div>
                <div class="border-t border-neutral-400 pt-1 text-[11px] text-neutral-600">
                    Authorized Signatory
                </div>
            </div>
        </div>
    </div>
</body>
</html>
