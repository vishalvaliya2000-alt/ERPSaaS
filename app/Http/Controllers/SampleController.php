<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sample;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Product;
use Carbon\Carbon;

class SampleController extends Controller
{
    public function index()
    {
        $samples = Sample::with(['customer', 'product'])
            ->orderByDesc('created_at')
            ->get();

        $customers = Customer::all();
        $leads = Lead::all();
        $products = Product::where('is_active', true)->get();

        return view('samples.index', compact('samples', 'customers', 'leads', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'uom' => 'nullable|string|max:20',
            'courier_provider' => 'nullable|string|max:100',
            'awb_number' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        $sampleNumber = 'SMP-' . date('Y') . '-' . rand(100, 999);

        $sample = Sample::create([
            'sample_number' => $sampleNumber,
            'customer_id' => $validated['customer_id'] ?? null,
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'uom' => $validated['uom'] ?: 'GMS',
            'courier_provider' => $validated['courier_provider'] ?: 'DTDC Express',
            'awb_number' => $validated['awb_number'] ?? null,
            'delivery_status' => 'IN_TRANSIT',
            'remarks' => $validated['remarks'] ?? null,
            'next_action_date' => Carbon::today()->addDays(5),
        ]);

        return back()->with('success', "✓ Sample {$sample->sample_number} dispatched successfully!");
    }

    public function update(Request $request, $id)
    {
        $sample = Sample::findOrFail($id);

        $validated = $request->validate([
            'delivery_status' => 'nullable|string',
            'feedback_rating' => 'nullable|integer|min:1|max:5',
            'customer_feedback' => 'nullable|string',
            'trial_result' => 'nullable|string',
            'courier_provider' => 'nullable|string|max:100',
            'awb_number' => 'nullable|string|max:100',
        ]);

        $sample->update($validated);

        return back()->with('success', "✓ Sample {$sample->sample_number} updated successfully!");
    }

    public function updateStatus(Request $request, $id)
    {
        $sample = Sample::findOrFail($id);

        $validated = $request->validate([
            'delivery_status' => 'nullable|string',
            'trial_status' => 'nullable|string',
            'customer_feedback' => 'nullable|string',
        ]);

        $sample->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'sample' => $sample,
                'message' => "Sample {$sample->sample_number} status updated.",
            ]);
        }

        return back()->with('success', "✓ Sample {$sample->sample_number} status updated successfully!");
    }

    public function destroy($id)
    {
        $sample = Sample::findOrFail($id);
        $num = $sample->sample_number;
        $sample->delete();

        return back()->with('success', "✓ Sample {$num} deleted successfully.");
    }
}