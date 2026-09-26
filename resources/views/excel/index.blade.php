@extends('layouts.app')

@section('title', 'Excel Reports & Export')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-neutral-200/80 shadow-xs">
        <div>
            <h2 class="text-xl font-black text-neutral-900">Excel Reports & Export</h2>
            <p class="text-xs text-neutral-500 mt-1">
                Generate and download comprehensive Excel reports from your live ERP database. 
                <br>
                <span class="font-bold text-neutral-800">Note: The ERP database is the single source of truth. Data must be entered manually into the system.</span>
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('excel.export') }}" class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs transition-colors">
                📊 Download Multi-Sheet Excel (.xlsx)
            </a>
        </div>
    </div>

    <!-- Migration Stats Breakdown -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-6">
        <div>
            <h3 class="font-bold text-base text-slate-900">Current Database Records</h3>
            <p class="text-xs text-slate-500">Overview of records available for export.</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Customers</span>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $stats['customers_count'] }}</p>
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Products</span>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $stats['products_count'] }}</p>
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Sales Orders</span>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $stats['orders_count'] }}</p>
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Shipments (LRs)</span>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $stats['shipments_count'] }}</p>
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Invoices</span>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $stats['invoices_count'] }}</p>
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Payment Receipts</span>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $stats['receipts_count'] }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
