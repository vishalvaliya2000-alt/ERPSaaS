<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\CommercialShipment;
use App\Models\CommercialShipmentItem;
use App\Models\Invoice;
use App\Models\PaymentReceipt;
use App\Models\FollowupTask;
use App\Models\Lead;
use App\Models\Sample;
use App\Models\AiInsight;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\BusinessDocument;
use App\Services\TenantManager;
use App\Services\DashboardAnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $tenantId = TenantManager::getTenantId();
        $user = auth()->user();
        $userName = $user?->first_name ?: ($user?->name ?: 'Vishal');

        // 1. FINANCIAL EXECUTIVE PULSE
        $totalSales = (float) Invoice::where('tenant_id', $tenantId)->sum('total_amount');
        $totalCollected = (float) PaymentReceipt::where('tenant_id', $tenantId)->where('payment_mode', '!=', 'ADVANCE_OFFSET')->sum('amount_received');
        $outstandingReceivables = (float) Invoice::where('tenant_id', $tenantId)->sum('balance_due');
        $totalExpenses = (float) \App\Models\PurchaseOrder::where('tenant_id', $tenantId)->sum('total_amount');
        $collectionRate = $totalSales > 0 ? round(($totalCollected / $totalSales) * 100, 1) : 0;
        $paidInvoicesCount = Invoice::where('tenant_id', $tenantId)->where('status', 'PAID')->count();
        $pendingInvoicesCount = Invoice::where('tenant_id', $tenantId)->whereIn('status', ['PENDING', 'ISSUED', 'PART_PAID', 'OVERDUE'])->where('balance_due', '>', 0)->count();

        // 2. FACTORY & FULFILLMENT VELOCITY
        $totalOrderedQty = (float) SalesOrderItem::where('tenant_id', $tenantId)->sum('order_qty');
        $totalDispatchedQty = (float) CommercialShipmentItem::where('tenant_id', $tenantId)->sum('quantity');
        $pendingShipmentQty = (float) SalesOrderItem::where('tenant_id', $tenantId)->whereIn('status', ['PENDING', 'PARTIAL'])->sum('balance_qty');
        $fulfillmentRate = $totalOrderedQty > 0 ? round(($totalDispatchedQty / $totalOrderedQty) * 100, 1) : 0;
        $openOrderLines = SalesOrderItem::where('tenant_id', $tenantId)->whereIn('status', ['PENDING', 'PARTIAL'])->count();

        // 3. PRODUCT-WISE DISPATCH PROGRESS (Live Factory Radar - Strict Tenant Isolated)
        $productDispatches = SalesOrderItem::with('product')
            ->where('tenant_id', $tenantId)
            ->select(
                'product_id',
                DB::raw('SUM(order_qty) as total_ordered'),
                DB::raw('SUM(shipped_qty) as total_shipped'),
                DB::raw('SUM(balance_qty) as total_balance')
            )
            ->groupBy('product_id')
            ->get()
            ->map(function ($item) {
                $ordered = (float) $item->total_ordered;
                $shipped = (float) $item->total_shipped;
                $balance = (float) $item->total_balance;
                $pct = $ordered > 0 ? round(($shipped / $ordered) * 100, 1) : 0;

                return [
                    'product_name' => $item->product?->product_name ?? 'Dehydrated Product',
                    'ordered_qty' => $ordered,
                    'shipped_qty' => $shipped,
                    'balance_qty' => $balance,
                    'progress_pct' => $pct,
                ];
            });

        // 4. PIPELINE & LEADS
        $leadsPipelineValue = (float) Lead::where('tenant_id', $tenantId)->whereNotIn('stage', ['WON', 'LOST'])->sum('estimated_value');
        $activeLeadsCount = Lead::where('tenant_id', $tenantId)->whereNotIn('stage', ['WON', 'LOST'])->count();
        $samplesInTrialCount = Sample::where('tenant_id', $tenantId)->whereIn('trial_status', ['Pending', 'Pending Evaluation', 'In Trial'])->count();

        // 5. TRACK SNOOZED / COMPLETED ACTIONS FOR INBOX ZERO LOGIC
        $seenEntityKeys = [];
        $futureTaskEntityKeys = [];
        $completedTodayEntityKeys = [];

        $futureTasks = FollowupTask::where('tenant_id', $tenantId)
            ->where('status', 'PENDING')
            ->whereDate('due_date', '>', Carbon::today())
            ->get();

        foreach ($futureTasks as $ft) {
            if ($ft->related_entity_type && $ft->related_entity_id) {
                $eType = strtolower($ft->related_entity_type);
                $futureTaskEntityKeys[$eType . '-' . $ft->related_entity_id] = true;
                if ($eType === 'invoice') $futureTaskEntityKeys['inv-' . $ft->related_entity_id] = true;
            }
        }

        $completedTodayTasks = FollowupTask::where('tenant_id', $tenantId)
            ->where('status', 'COMPLETED')
            ->whereDate('completed_at', Carbon::today())
            ->get();

        foreach ($completedTodayTasks as $ct) {
            if ($ct->related_entity_type && $ct->related_entity_id) {
                $eType = strtolower($ct->related_entity_type);
                $completedTodayEntityKeys[$eType . '-' . $ct->related_entity_id] = true;
                if ($eType === 'invoice') $completedTodayEntityKeys['inv-' . $ct->related_entity_id] = true;
            }
            $completedTodayEntityKeys['task-' . $ct->id] = true;
        }

        // 6. HIGH-IMPACT DECISION QUEUE (Unified & Strictly Deduplicated)
        $decisionQueue = collect();

        // Active Tasks from FollowupTask table
        $activeTasks = FollowupTask::with(['customer', 'lead'])
            ->where('tenant_id', $tenantId)
            ->where('status', 'PENDING')
            ->whereDate('due_date', '<=', Carbon::today()->addDays(1))
            ->orderByRaw("CASE WHEN priority = 'URGENT' THEN 1 WHEN priority = 'HIGH' THEN 2 WHEN priority = 'MEDIUM' THEN 3 ELSE 4 END")
            ->orderBy('due_date')
            ->get();

        foreach ($activeTasks as $t) {
            // Check if this task is for an on-demand order that should be skipped (e.g. PO-132)
            $reasonLower = strtolower($t->reason . ' ' . $t->pending_item);
            if (str_contains($reasonLower, 'po-132') || str_contains($reasonLower, 'on-demand') || str_contains($reasonLower, 'on demand')) {
                // Delete stale task from database
                $t->delete();
                continue;
            }

            // Auto-resolve tasks if the underlying invoice is already paid
            if ($t->type === 'PAYMENT_FOLLOWUP') {
                if (preg_match('/(INV-[0-9]+|BOS[0-9]+)/i', $t->reason . ' ' . $t->pending_item, $invMatch)) {
                    $inv = Invoice::where('tenant_id', $tenantId)->where('invoice_number', $invMatch[0])->first();
                    if ($inv && ($inv->balance_due <= 0 || $inv->status === 'PAID')) {
                        $t->update(['status' => 'COMPLETED', 'completed_at' => Carbon::now()]);
                        continue;
                    }
                }
            }

            // Auto-resolve if sales order is already completed
            if ($t->type === 'SALES_FOLLOWUP') {
                if (preg_match('/(PO[- ][0-9a-z\/]+)/i', $t->reason . ' ' . $t->pending_item, $poMatch)) {
                    $cleanPo = trim($poMatch[0]);
                    $so = SalesOrder::where('tenant_id', $tenantId)->where(function($q) use ($cleanPo) {
                        $q->where('order_number', $cleanPo)->orWhere('po_number', $cleanPo);
                    })->first();
                    if ($so && ($so->status === 'COMPLETED' || $so->isFullyDelivered())) {
                        $t->update(['status' => 'COMPLETED', 'completed_at' => Carbon::now()]);
                        continue;
                    }
                }
            }

            // Auto-resolve if shipment is already delivered
            if ($t->type === 'DELIVERY_CONFIRMATION') {
                if (preg_match('/LR\s*([0-9]+)/i', $t->reason . ' ' . $t->pending_item, $lrMatch)) {
                    $shp = CommercialShipment::where('tenant_id', $tenantId)->where('lr_number', $lrMatch[1])->first();
                    if ($shp && $shp->status === 'DELIVERED') {
                        $t->update(['status' => 'COMPLETED', 'completed_at' => Carbon::now()]);
                        continue;
                    }
                }
            }

            $entityType = $t->related_entity_type ? strtolower($t->related_entity_type) : 'task';
            $entityId = $t->related_entity_id ?: $t->id;
            $key = "{$entityType}-{$entityId}";

            if (isset($seenEntityKeys[$key])) continue;
            $seenEntityKeys[$key] = true;
            if ($entityType === 'invoice') $seenEntityKeys['inv-' . $entityId] = true;

            $rel = formatRelativeDate($t->due_date);
            $compName = $t->customer?->company_name ?? $t->lead?->company_name ?? 'Priority Follow-up';
            $contact = $t->customer?->primary_contact_person ?? $t->lead?->contact_person ?? 'Sir';
            $phone = $t->customer?->primary_phone ?? $t->lead?->phone ?? '';

            $badgeColor = match($t->priority) {
                'URGENT' => 'rose',
                'HIGH' => 'amber',
                'MEDIUM' => 'blue',
                default => 'slate',
            };

            $decisionQueue->push([
                'id' => $key,
                'task_id' => $t->id,
                'type' => 'TASK',
                'badge' => str_replace('_', ' ', $t->type),
                'badge_color' => $badgeColor,
                'customer_name' => $compName,
                'contact_person' => $contact,
                'phone' => $phone,
                'title' => $t->reason,
                'next_action' => $t->next_action,
                'amount' => null,
                'due_date_text' => $rel['text'],
                'is_overdue' => $rel['isOverdue'],
                'suggested_message' => "Hi {$contact}, following up regarding {$t->reason}. Please let me know how you would like to proceed.",
            ]);
        }

        // 7. ACTIVE CONSIGNMENTS IN-TRANSIT (Transporter Feed)
        $activeShipments = CommercialShipment::with(['salesOrder.customer', 'items.salesOrderItem.product'])
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['DISPATCHED', 'IN_TRANSIT'])
            ->orderByDesc('shipment_date')
            ->take(6)
            ->get()
            ->map(function ($shp) {
                $firstItem = $shp->items->first();
                $prodName = $firstItem ? ($firstItem->salesOrderItem?->product?->product_name ?? 'Dehydrated goods') : 'Dehydrated goods';
                $qty = $shp->items->sum('quantity');
                $cust = $shp->salesOrder?->customer ?? $shp->items->first()?->salesOrderItem?->salesOrder?->customer;
                $contactName = $cust?->primary_contact_person ?: ($cust?->company_name ?: 'there');

                return [
                    'id' => 'shipment-' . $shp->id,
                    'shipment_id' => $shp->id,
                    'title' => "LR {$shp->lr_number} · {$shp->customer_names}",
                    'transporter' => $shp->transporter ?: 'Direct Dispatch',
                    'lr_number' => $shp->lr_number,
                    'destination' => $shp->destination ?: ($cust?->city ?? 'Destination Godown'),
                    'tracking_url' => $shp->tracking_url,
                    'status' => $shp->status ?: 'IN_TRANSIT',
                    'qty_text' => number_format($qty) . " KG ({$prodName})",
                    'po_numbers' => $shp->po_numbers,
                    'date' => $shp->shipment_date->format('d M Y'),
                    'contact_person' => $contactName,
                    'phone' => $cust?->primary_phone ?? '',
                    'suggested_message' => "Hi {$contactName}, dispatch update: your consignment under LR {$shp->lr_number} ({$shp->transporter}) is currently in transit to {$shp->destination}. Expected delivery shortly.",
                ];
            });

        // 8. KEY ACCOUNTS SUMMARY
        $keyAccounts = Customer::with(['salesOrders', 'invoices'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('total_revenue')
            ->take(4)
            ->get();

        // 9. DAILY EXECUTION PROGRESS
        $completedTodayCount = $completedTodayTasks->count();
        $pendingDecisionCount = $decisionQueue->count();
        $totalDailyActions = $pendingDecisionCount + $completedTodayCount;
        $dailyProgressPct = ($totalDailyActions > 0) ? round(($completedTodayCount / $totalDailyActions) * 100) : 100;

        // 10. AI EXECUTIVE REAL-TIME BRIEFING
        $hour = Carbon::now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

        $summaryParts = [];
        if ($outstandingReceivables > 0) {
            $summaryParts[] = "You have " . formatINR($outstandingReceivables) . " in outstanding receivables (" . $collectionRate . "% collected to date)";
        } elseif ($totalSales > 0) {
            $summaryParts[] = "All issued invoices are 100% collected (" . formatINR($totalCollected) . ")";
        }

        if ($pendingShipmentQty > 0) {
            $summaryParts[] = number_format($pendingShipmentQty) . " KG pending dispatch across {$openOrderLines} open sales order line(s)";
        }

        $activeShipmentsCount = $activeShipments->count();
        if ($activeShipmentsCount > 0) {
            $summaryParts[] = "{$activeShipmentsCount} consignment(s) currently in transit";
        }

        if (empty($summaryParts)) {
            $summary = "Welcome to your ERP Command Center. Your workspace is active with 0 pending bottlenecks. Add your first customer or sales order to begin tracking operations.";
        } else {
            $summary = implode(' and ', $summaryParts) . '.';
        }

        $aiBriefing = [
            'greeting' => $greeting,
            'summary' => $summary,
            'focus_items_count' => $pendingDecisionCount,
        ];

        // 11. DOCUMENT INTELLIGENCE & DOCUMENTS REQUIRING ATTENTION
        $now = Carbon::now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $thisMonthEnd = $now->copy()->endOfMonth();
        $today = Carbon::today();

        // Metric 1: POs this month
        $posThisMonthQuery = BusinessDocument::where('tenant_id', $tenantId)
            ->where('document_type', 'PO')
            ->where(function ($q) use ($thisMonthStart, $thisMonthEnd) {
                $q->whereBetween('document_date', [$thisMonthStart, $thisMonthEnd])
                  ->orWhereBetween('created_at', [$thisMonthStart, $thisMonthEnd]);
            });
        $posThisMonthCount = (clone $posThisMonthQuery)->count();
        $posThisMonthValue = (float) (clone $posThisMonthQuery)->sum('total_amount');
        if ($posThisMonthCount === 0) {
            $soThisMonth = SalesOrder::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd]);
            $posThisMonthCount = (clone $soThisMonth)->count();
            $posThisMonthValue = (float) (clone $soThisMonth)->sum('total_amount');
        }

        // Metric 2: Invoices this month
        $invoicesThisMonthQuery = Invoice::where('tenant_id', $tenantId)
            ->where(function ($q) use ($thisMonthStart, $thisMonthEnd) {
                $q->whereBetween('invoice_date', [$thisMonthStart, $thisMonthEnd])
                  ->orWhereBetween('created_at', [$thisMonthStart, $thisMonthEnd]);
            });
        $invoicesThisMonthCount = (clone $invoicesThisMonthQuery)->count();
        $invoicesThisMonthValue = (float) (clone $invoicesThisMonthQuery)->sum('total_amount');

        // Total documents breakdown
        $totalPOsCount = BusinessDocument::where('tenant_id', $tenantId)->where('document_type', 'PO')->count();
        if ($totalPOsCount === 0) {
            $totalPOsCount = SalesOrder::where('tenant_id', $tenantId)->count();
        }
        $totalInvoicesCount = Invoice::where('tenant_id', $tenantId)->count();
        $totalLRsCount = CommercialShipment::where('tenant_id', $tenantId)->count();

        // Dispatched but not invoiced
        $dispatchedNotInvoiced = CommercialShipment::where('tenant_id', $tenantId)
            ->whereDoesntHave('invoices')
            ->with(['salesOrder.customer'])
            ->get();
        $dispatchedNotInvoicedCount = $dispatchedNotInvoiced->count();

        // Delivered but payment pending
        $deliveredPendingPaymentCount = CommercialShipment::where('tenant_id', $tenantId)
            ->where('status', 'DELIVERED')
            ->whereHas('invoices', function ($q) {
                $q->where('balance_due', '>', 0);
            })
            ->count();

        // Overdue & Pending Payment invoices
        $overdueInvoices = Invoice::where('tenant_id', $tenantId)
            ->where('balance_due', '>', 0)
            ->where(function ($q) use ($today) {
                $q->where('status', 'OVERDUE')
                  ->orWhere('due_date', '<', $today);
            })
            ->with(['customer', 'salesOrder'])
            ->orderBy('due_date', 'asc')
            ->get();
        $overdueInvoicesCount = $overdueInvoices->count();

        // Invoices due within 7 days
        $dueSoonInvoices = Invoice::where('tenant_id', $tenantId)
            ->where('balance_due', '>', 0)
            ->whereBetween('due_date', [$today, $today->copy()->addDays(7)])
            ->with(['customer', 'salesOrder'])
            ->orderBy('due_date', 'asc')
            ->get();

        // Open POs with no dispatches/shipments
        $unshippedOrders = SalesOrder::where('tenant_id', $tenantId)
            ->whereIn('status', ['PENDING', 'CONFIRMED', 'PROCESSING'])
            ->whereDoesntHave('shipments')
            ->with(['customer'])
            ->latest()
            ->take(5)
            ->get();
        $unshippedOrdersCount = SalesOrder::where('tenant_id', $tenantId)
            ->whereIn('status', ['PENDING', 'CONFIRMED', 'PROCESSING'])
            ->whereDoesntHave('shipments')
            ->count();

        $documentMetrics = [
            'pos_this_month_count' => $posThisMonthCount,
            'pos_this_month_value' => $posThisMonthValue,
            'invoices_this_month_count' => $invoicesThisMonthCount,
            'invoices_this_month_value' => $invoicesThisMonthValue,
            'total_pos' => $totalPOsCount,
            'total_invoices' => $totalInvoicesCount,
            'total_lrs' => $totalLRsCount,
            'pending_payment_count' => $pendingInvoicesCount,
            'overdue_count' => $overdueInvoicesCount,
            'dispatched_not_invoiced_count' => $dispatchedNotInvoicedCount,
            'delivered_pending_payment_count' => $deliveredPendingPaymentCount,
            'unshipped_pos_count' => $unshippedOrdersCount,
        ];

        // Build prioritized Documents Requiring Attention collection
        $documentsAttention = collect();

        // 1. Overdue Invoices (High Severity - Red)
        foreach ($overdueInvoices->take(4) as $inv) {
            $daysOverdue = $inv->due_date ? max(1, (int)$inv->due_date->diffInDays($today)) : 0;
            $documentsAttention->push([
                'type' => 'INVOICE',
                'severity' => 'red',
                'badge' => "{$daysOverdue}d Overdue",
                'title' => "Invoice #{$inv->invoice_number}",
                'subtitle' => $inv->customer?->company_name ?: 'Unknown Customer',
                'amount' => $inv->balance_due,
                'description' => "Balance ₹" . number_format($inv->balance_due) . " pending since " . ($inv->due_date ? $inv->due_date->format('d M Y') : 'N/A'),
                'action_label' => 'Collect / View',
                'action_url' => route('invoices.index') . "?search=" . urlencode($inv->invoice_number),
                'doc_url' => route('invoices.index') . "?search=" . urlencode($inv->invoice_number),
            ]);
        }

        // 2. Dispatches with No Invoice (Warning Severity - Amber)
        foreach ($dispatchedNotInvoiced->take(3) as $shp) {
            $documentsAttention->push([
                'type' => 'SHIPMENT_UNINVOICED',
                'severity' => 'amber',
                'badge' => 'Uninvoiced Dispatch',
                'title' => "Shipment #{$shp->shipment_number}" . ($shp->lr_number ? " (LR: {$shp->lr_number})" : ''),
                'subtitle' => $shp->salesOrder?->customer?->company_name ?: ($shp->transporter ?: 'Direct Dispatch'),
                'amount' => null,
                'description' => "Dispatched on " . ($shp->shipment_date ? $shp->shipment_date->format('d M Y') : 'N/A') . " — Tax Invoice not generated yet",
                'action_label' => 'Generate Invoice',
                'action_url' => route('invoices.index'),
                'doc_url' => route('shipments.index') . "?search=" . urlencode($shp->shipment_number),
            ]);
        }

        // 3. Open POs with no shipments (Information - Blue)
        foreach ($unshippedOrders->take(3) as $ord) {
            $daysSinceOrder = $ord->created_at ? (int)$ord->created_at->diffInDays($today) : 0;
            $documentsAttention->push([
                'type' => 'PO_UNSHIPPED',
                'severity' => 'blue',
                'badge' => 'Unshipped PO',
                'title' => "PO #{$ord->order_number}",
                'subtitle' => $ord->customer?->company_name ?: 'Direct Customer',
                'amount' => $ord->total_amount,
                'description' => "₹" . number_format($ord->total_amount) . " confirmed {$daysSinceOrder}d ago — awaiting factory consignment/LR",
                'action_label' => 'Create Dispatch',
                'action_url' => route('shipments.index'),
                'doc_url' => route('orders.index') . "?search=" . urlencode($ord->order_number),
            ]);
        }

        // 4. Invoices Due Within 7 Days (Yellow)
        foreach ($dueSoonInvoices->take(3) as $inv) {
            $daysLeft = $inv->due_date ? max(0, (int)$today->diffInDays($inv->due_date)) : 0;
            $documentsAttention->push([
                'type' => 'INVOICE_DUE_SOON',
                'severity' => 'yellow',
                'badge' => "Due in {$daysLeft}d",
                'title' => "Invoice #{$inv->invoice_number}",
                'subtitle' => $inv->customer?->company_name ?: 'Customer',
                'amount' => $inv->balance_due,
                'description' => "₹" . number_format($inv->balance_due) . " due on " . ($inv->due_date ? $inv->due_date->format('d M Y') : 'N/A'),
                'action_label' => 'Send Reminder',
                'action_url' => route('invoices.index') . "?search=" . urlencode($inv->invoice_number),
                'doc_url' => route('invoices.index') . "?search=" . urlencode($inv->invoice_number),
            ]);
        }

        // 12. COMPREHENSIVE EXECUTIVE ANALYTICS (SaaS ERP Intelligence)
        $analyticsService = app(DashboardAnalyticsService::class);
        $analytics = $analyticsService->getAllAnalytics($tenantId);

        return view('dashboard', compact(
            'totalSales',
            'totalCollected',
            'outstandingReceivables',
            'totalExpenses',
            'collectionRate',
            'paidInvoicesCount',
            'pendingInvoicesCount',
            'totalOrderedQty',
            'totalDispatchedQty',
            'pendingShipmentQty',
            'fulfillmentRate',
            'openOrderLines',
            'productDispatches',
            'leadsPipelineValue',
            'activeLeadsCount',
            'samplesInTrialCount',
            'decisionQueue',
            'activeShipments',
            'keyAccounts',
            'completedTodayCount',
            'dailyProgressPct',
            'aiBriefing',
            'analytics',
            'documentMetrics',
            'documentsAttention'
        ));
    }

    /**
     * Async Dashboard Analytics Endpoint for fast client-side reactive filtering
     */
    public function analytics(Request $request, DashboardAnalyticsService $service)
    {
        $tenantId = TenantManager::getTenantId();
        $filters = [
            'period' => $request->query('period', 'this_fy'),
            'fy' => $request->query('fy'),
            'customer_id' => $request->query('customer_id'),
            'product_id' => $request->query('product_id'),
        ];

        $data = $service->getAllAnalytics($tenantId, $filters);

        return response()->json($data);
    }

    /**
     * On-Demand Trigger: Clean previous follow-ups & scan live data for active tenant
     */
    public function generateActions(Request $request)
    {
        $tenantId = TenantManager::getTenantId();
        
        // Run the Artisan Command for this specific tenant
        \Illuminate\Support\Facades\Artisan::call('erp:generate-daily-actions', [
            '--tenant' => $tenantId,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Today's Priority Decision Queue refreshed with live scanned data!",
            ]);
        }

        return redirect()->route('dashboard')->with('success', "Today's Priority Decision Queue refreshed with live scanned data!");
    }

    /**
     * Get compiled Evening Executive Briefing for active tenant
     */
    public function dailyDigest(Request $request)
    {
        $tenantId = TenantManager::getTenantId();
        $tenant = TenantManager::getTenant();
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        // 1. Dispatches
        $todayShipments = CommercialShipment::with(['salesOrder.customer', 'items.salesOrderItem.product'])
            ->where('tenant_id', $tenantId)
            ->whereDate('shipment_date', $today)
            ->get();

        $totalDispatchedKg = 0;
        $shipmentItems = [];
        foreach ($todayShipments as $shp) {
            $qty = (float) $shp->items->sum('quantity');
            $totalDispatchedKg += $qty;
            $cust = $shp->salesOrder?->customer ?? $shp->items->first()?->salesOrderItem?->salesOrder?->customer;
            $firstProd = $shp->items->first()?->salesOrderItem?->product?->product_name ?? 'Dehydrated goods';
            $shipmentItems[] = [
                'lr' => $shp->lr_number ?: $shp->shipment_number,
                'transporter' => $shp->transporter ?: 'Direct',
                'tracking_url' => $shp->tracking_url,
                'customer' => $cust?->company_name ?? 'Buyer',
                'product' => $firstProd,
                'destination' => $shp->destination ?: ($cust?->city ?? 'Godown'),
                'qty' => $qty,
            ];
        }

        // 2. Collections
        $todayReceipts = PaymentReceipt::with('customer')
            ->where('tenant_id', $tenantId)
            ->where('payment_mode', '!=', 'ADVANCE_OFFSET')
            ->whereDate('receipt_date', $today)
            ->get();

        $totalCollected = (float) $todayReceipts->sum('amount_received');
        $receiptItems = [];
        foreach ($todayReceipts as $rec) {
            $receiptItems[] = [
                'customer' => $rec->customer?->company_name ?? 'Customer',
                'amount' => (float) $rec->amount_received,
                'mode' => $rec->payment_mode ?: 'RTGS',
                'ref' => $rec->reference_number,
            ];
        }

        // 3. New Orders
        $todayOrders = SalesOrder::with(['customer', 'items'])
            ->where('tenant_id', $tenantId)
            ->whereDate('order_date', $today)
            ->get();

        $totalOrderValue = (float) $todayOrders->sum('total_amount');
        $totalOrderKg = 0;
        $orderItems = [];
        foreach ($todayOrders as $ord) {
            $qty = (float) $ord->items->sum('order_qty');
            $totalOrderKg += $qty;
            $orderItems[] = [
                'po' => $ord->po_number ?: $ord->order_number,
                'customer' => $ord->customer?->company_name ?? 'Client',
                'amount' => (float) $ord->total_amount,
                'qty' => $qty,
            ];
        }

        // 4. Tomorrow's Radar
        $tomorrowInvoices = Invoice::with('customer')
            ->where('tenant_id', $tenantId)
            ->whereDate('due_date', $tomorrow)
            ->where('balance_due', '>', 0)
            ->get();

        $totalDueTomorrow = (float) $tomorrowInvoices->sum('balance_due');
        $tomorrowItems = [];
        foreach ($tomorrowInvoices as $inv) {
            $tomorrowItems[] = [
                'invoice_number' => $inv->invoice_number,
                'customer' => $inv->customer?->company_name ?? 'Customer',
                'balance_due' => (float) $inv->balance_due,
            ];
        }

        // Formatted WhatsApp message
        $waText = "🌙 *EVENING EXECUTIVE DIGEST* — {$tenant->name}\n";
        $waText .= "📅 " . $today->format('l, d F Y') . " (7:00 PM Daily Briefing)\n";
        $waText .= "━━━━━━━━━━━━━━━━━━━━\n\n";

        $waText .= "🚚 *TODAY'S FACTORY DISPATCHES*\n";
        if (!empty($shipmentItems)) {
            $waText .= "• Total Loaded: " . number_format($totalDispatchedKg) . " KG (" . count($shipmentItems) . " consignments)\n";
            foreach ($shipmentItems as $si) {
                $waText .= "  - LR {$si['lr']} ({$si['transporter']}): " . number_format($si['qty']) . " KG {$si['product']} to {$si['destination']} ({$si['customer']})\n";
            }
        } else {
            $waText .= "• Zero dispatches loaded today.\n";
        }
        $waText .= "\n";

        $waText .= "💰 *TODAY'S CASH COLLECTIONS (RTGS/BANK)*\n";
        if (!empty($receiptItems)) {
            $waText .= "• Total Received: " . formatINR($totalCollected) . " across " . count($receiptItems) . " receipt(s)\n";
            foreach ($receiptItems as $ri) {
                $waText .= "  - {$ri['customer']}: " . formatINR($ri['amount']) . " ({$ri['mode']})\n";
            }
        } else {
            $waText .= "• No bank credits or payment receipts recorded today.\n";
        }
        $waText .= "\n";

        $waText .= "📝 *NEW CONTRACTS BOOKED TODAY*\n";
        if (!empty($orderItems)) {
            $waText .= "• Total Booked: " . formatINR($totalOrderValue) . " (" . number_format($totalOrderKg) . " KG)\n";
            foreach ($orderItems as $oi) {
                $waText .= "  - PO {$oi['po']} ({$oi['customer']}): " . formatINR($oi['amount']) . "\n";
            }
        } else {
            $waText .= "• No new sales contracts logged today.\n";
        }
        $waText .= "\n";

        $waText .= "🚨 *TOMORROW'S RECEIVABLES RADAR*\n";
        if (!empty($tomorrowItems)) {
            $waText .= "• Total Due Tomorrow: " . formatINR($totalDueTomorrow) . " (" . count($tomorrowItems) . " invoice(s))\n";
            foreach ($tomorrowItems as $ti) {
                $waText .= "  - Inv {$ti['invoice_number']} ({$ti['customer']}): " . formatINR($ti['balance_due']) . "\n";
            }
        } else {
            $waText .= "• 0 customer invoices due tomorrow.\n";
        }
        $waText .= "━━━━━━━━━━━━━━━━━━━━";

        return response()->json([
            'tenant_name' => $tenant->name,
            'date_formatted' => $today->format('l, d F Y'),
            'total_dispatched_kg' => $totalDispatchedKg,
            'shipments_count' => count($shipmentItems),
            'shipment_items' => $shipmentItems,
            'total_collected' => $totalCollected,
            'receipts_count' => count($receiptItems),
            'receipt_items' => $receiptItems,
            'total_order_value' => $totalOrderValue,
            'total_order_kg' => $totalOrderKg,
            'order_items' => $orderItems,
            'total_due_tomorrow' => $totalDueTomorrow,
            'tomorrow_items' => $tomorrowItems,
            'whatsapp_text' => $waText,
        ]);
    }
}