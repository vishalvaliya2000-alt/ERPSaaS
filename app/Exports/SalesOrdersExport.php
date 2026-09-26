<?php

namespace App\Exports;

use App\Models\SalesOrder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SalesOrdersExport implements FromArray, WithHeadings, WithTitle
{
    public function array(): array
    {
        $rows = [];
        $orders = SalesOrder::with(['customer', 'items.product'])->get();

        foreach ($orders as $so) {
            foreach ($so->items as $it) {
                $rows[] = [
                    $so->order_number,
                    $so->order_date ? $so->order_date->format('Y-m-d') : '',
                    $so->customer->company_name ?? '',
                    $it->product->product_name ?? '',
                    (float) $it->order_qty,
                    (float) $it->rate,
                    (float) $it->order_value,
                    (float) $it->shipped_qty,
                    (float) $it->balance_qty,
                    $it->status,
                ];
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Order No.',
            'Order Date',
            'Customer',
            'Product',
            'Order Qty',
            'Rate',
            'Order Value',
            'Shipped Qty',
            'Balance Qty',
            'Status',
        ];
    }

    public function title(): string
    {
        return 'Sales Orders';
    }
}
