<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\CommercialShipment;
use App\Models\Invoice;
use App\Models\PaymentReceipt;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MasterWorkbookExport;

class ExcelController extends Controller
{
    public function index()
    {
        $stats = [
            'customers_count' => Customer::count(),
            'products_count' => Product::count(),
            'orders_count' => SalesOrder::count(),
            'order_items_count' => SalesOrderItem::count(),
            'shipments_count' => CommercialShipment::count(),
            'invoices_count' => Invoice::count(),
            'receipts_count' => PaymentReceipt::count(),
        ];

        return view('excel.index', compact('stats'));
    }



    public function export()
    {
        try {
            return Excel::download(
                new MasterWorkbookExport(),
                'ERP_Master_Export_' . date('Y-m-d') . '.xlsx'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Master Excel export failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return back()->with('error', 'Failed to generate Excel export: ' . $e->getMessage());
        }
    }
}
