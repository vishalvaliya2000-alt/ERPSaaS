@extends('layouts.app')

@section('title', 'Product Catalog & HSN Rates')

@section('content')
<div class="space-y-6" x-data="productsManager()">
    <!-- Header -->
    <div
        class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-neutral-200/80 shadow-xs">
        <div>
            <h2 class="text-xl font-black text-neutral-900 flex items-center gap-2">
                <span>📦</span> Products
            </h2>
            <p class="text-xs text-neutral-500 mt-1">
                Standard Dehydration Export SKUs (DGP-100, DWOP-100, DTWOP-100, DCAB-F, DCAR-F), packaging specs, and
                live GST HSN master.
            </p>
        </div>

        <!-- Action & Filter Controls -->
        <div class="flex items-center gap-2 flex-wrap">
            <form method="GET" action="{{ route('products.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
                <input type="text" name="q" placeholder="Search SKU, name, or HSN..." value="{{ $search }}"
                    class="px-3.5 py-2 text-xs border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#f53003] w-full sm:w-56 font-medium">
            </form>

            <button @click="openAddModal()"
                class="px-4 py-2.5 bg-[#091315] hover:bg-black text-[#D7FF53] font-extrabold text-xs rounded-full shadow-2xs transition-all flex items-center gap-2 cursor-pointer border border-neutral-800 shrink-0">
                <span>➕</span>
                <span>Add Product</span>
            </button>
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700">Catalog SKUs ({{ $products->count() }})</span>
            <span class="text-[11px] text-slate-500">Auto-populates items with HSN, GST rate, and packaging in Orders &
                Invoices</span>
        </div>

        @if($products->isEmpty())
        <div class="p-12 text-center space-y-3">
            <div
                class="w-14 h-14 rounded-3xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl mx-auto font-bold shadow-xs">
                📦
            </div>
            <h3 class="text-sm font-bold text-slate-800">No Products in Catalog</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">Add your standard Dehydrated Onion, Garlic, and Vegetable
                products with SKU codes, packaging specifications, and reference market rates.</p>
            <button @click="openAddModal()"
                class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-xs cursor-pointer">
                + Add First Product
            </button>
        </div>
        @else
        <div class="overflow-x-auto touch-scroll">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-100/80 text-slate-700 font-bold border-b border-slate-200">
                        <th class="p-3.5">SKU Code</th>
                        <th class="p-3.5">Product Description & Cut</th>
                        <th class="p-3.5">Category</th>
                        <th class="p-3.5">HSN Code</th>
                        <th class="p-3.5 text-right">Market Reference Rate / KG</th>
                        <th class="p-3.5 text-center">GST</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($products as $prod)
                    @php
                    $nameLower = strtolower($prod->product_name);
                    if (str_contains($nameLower, 'garlic')) {
                    $catLabel = 'Garlic';
                    $catClass = 'bg-amber-100 text-amber-900 border-amber-200';
                    } elseif (str_contains($nameLower, 'onion')) {
                    $catLabel = 'Onion';
                    $catClass = 'bg-rose-100 text-rose-900 border-rose-200';
                    } elseif (str_contains($nameLower, 'cabbage') || str_contains($nameLower, 'carrot') ||
                    str_contains($nameLower, 'beet') || str_contains($nameLower, 'spinach') || str_contains($nameLower,
                    'tomato') || str_contains($nameLower, 'potato') || str_contains($nameLower, 'vegetable')) {
                    $catLabel = 'Vegetables';
                    $catClass = 'bg-emerald-100 text-emerald-900 border-emerald-200';
                    } elseif (str_contains($nameLower, 'ginger') || str_contains($nameLower, 'turmeric') ||
                    str_contains($nameLower, 'coriander') || str_contains($nameLower, 'cumin') ||
                    str_contains($nameLower, 'methi') || str_contains($nameLower, 'chilli') || str_contains($nameLower,
                    'spice')) {
                    $catLabel = 'Spices';
                    $catClass = 'bg-purple-100 text-purple-900 border-purple-200';
                    } elseif (str_contains($nameLower, 'box') || str_contains($nameLower, 'bag') ||
                    str_contains($nameLower, 'liner') || str_contains($nameLower, 'raffia')) {
                    $catLabel = 'Packaging';
                    $catClass = 'bg-slate-100 text-slate-800 border-slate-200';
                    } else {
                    $catLabel = 'Agro';
                    $catClass = 'bg-blue-100 text-blue-900 border-blue-200';
                    }
                    @endphp
                    <tr class="hover:bg-amber-50/40 transition-colors">
                        <td class="p-3.5 font-mono font-black text-slate-900 text-sm tracking-wide">
                            <span class="px-2 py-0.5 rounded-lg bg-slate-100 border border-slate-200">{{
                                $prod->product_code }}</span>
                        </td>
                        <td class="p-3.5">
                            <span class="font-bold text-slate-900 text-sm block">{{ $prod->product_name }}</span>
                            <span class="text-[11px] text-slate-500">{{ $prod->packaging ?: '20 KGs Two Poly Liner Bags
                                with Laminated Paper Bag' }}</span>
                        </td>
                        <td class="p-3.5">
                            <span
                                class="text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase border {{ $catClass }}">
                                {{ $catLabel }}
                            </span>
                        </td>
                        <td class="p-3.5 font-mono font-bold text-slate-700">
                            <span class="px-2 py-0.5 rounded bg-slate-50 border border-slate-200">{{ $prod->hsn_code ?:
                                '07129090' }}</span>
                        </td>
                        <td class="p-3.5 text-right font-black text-slate-900 text-sm">
                            ₹{{ number_format($prod->standard_rate ?: $prod->current_rate_per_kg, 2) }}
                            <span class="text-[10px] text-slate-400 font-normal block">Base Rate</span>
                        </td>
                        <td class="p-3.5 text-center">
                            <span
                                class="px-2.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-bold text-[11px]">
                                {{ number_format($prod->tax_rate_percent > 0 ? $prod->tax_rate_percent : 0, 0) }}%
                            </span>
                        </td>
                        <td class="p-3.5 text-right space-x-2">
                            <button @click="openEditModal(@js($prod))"
                                class="px-2.5 py-1 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors cursor-pointer">
                                Edit
                            </button>
                            <form action="{{ route('products.destroy', $prod->id) }}" method="POST" class="inline-block"
                                onsubmit="return confirm('Delete product {{ $prod->product_name }}?');">
                                @csrf
                                <button type="submit"
                                    class="px-2.5 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <!-- Datalist of Standard Dehydration Cuts for Autocomplete -->
    <datalist id="standardCutsList">
        <option value="Dehydrated Garlic Flakes (A-Grade)"></option>
        <option value="Dehydrated Garlic Chopped (3-5 mm)"></option>
        <option value="Dehydrated Garlic Minced (1-3 mm)"></option>
        <option value="Dehydrated Garlic Granules (40-60 Mesh)"></option>
        <option value="Dehydrated Garlic Powder (80-100 Mesh)"></option>
        <option value="Dehydrated White Onion Flakes / Kibbled"></option>
        <option value="Dehydrated White Onion Chopped (3-5 mm)"></option>
        <option value="Dehydrated White Onion Minced (1-3 mm)"></option>
        <option value="Dehydrated White Onion Granules (40-60 Mesh)"></option>
        <option value="Dehydrated White Onion Powder (80-100 Mesh)"></option>
        <option value="Dehydrated Red Onion Flakes / Kibbled"></option>
        <option value="Dehydrated Red Onion Chopped (3-5 mm)"></option>
        <option value="Dehydrated Red Onion Minced (1-3 mm)"></option>
        <option value="Dehydrated Red Onion Granules (40-60 Mesh)"></option>
        <option value="Dehydrated Red Onion Powder (80-100 Mesh)"></option>
        <option value="Dehydrated Pink Onion Flakes / Kibbled"></option>
        <option value="Dehydrated Pink Onion Chopped (3-5 mm)"></option>
        <option value="Dehydrated Pink Onion Minced (1-3 mm)"></option>
        <option value="Dehydrated Pink Onion Granules (40-60 Mesh)"></option>
        <option value="Dehydrated Pink Onion Powder (80-100 Mesh)"></option>
        <option value="Dehydrated Spring Onion Flakes"></option>
        <option value="Dehydrated Spring Onion Powder (80-100 Mesh)"></option>
        <option value="Dehydrated Cabbage Flakes (3-5 mm)"></option>
        <option value="Dehydrated Cabbage Powder (80-100 Mesh)"></option>
        <option value="Dehydrated Carrot Flakes (3-5 mm)"></option>
        <option value="Dehydrated Carrot Powder (80-100 Mesh)"></option>
        <option value="Dehydrated Beetroot Powder (80-100 Mesh)"></option>
        <option value="Dehydrated Beetroot Flakes (3-5 mm)"></option>
        <option value="Dehydrated Spinach Powder (Palak Powder)"></option>
        <option value="Dehydrated Tomato Powder"></option>
        <option value="Dehydrated Ginger Flakes"></option>
        <option value="Dehydrated Ginger Powder (Sonth Powder)"></option>
        <option value="Dehydrated Kasuri Methi (Fenugreek Leaves)"></option>
        <option value="Dehydrated Toasted White Onion Flakes"></option>
        <option value="Dehydrated Toasted White Onion Powder (80-100 Mesh)"></option>
        <option value="Dehydrated Toasted Garlic Powder (80-100 Mesh)"></option>
        <option value="Fried Crispy Garlic Flakes"></option>
        <option value="Fried Crispy Onion (Biryani Cut)"></option>
    </datalist>

    <!-- Add Product Modal (with Smart Auto-Generation & Online HSN Search API) -->
    <template x-teleport="body">
        <div x-show="showAddModal" x-cloak
            class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="showAddModal = false"
                class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 max-w-xl w-full overflow-hidden max-h-[92vh] flex flex-col text-xs">
                <!-- Modal Header -->
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center text-lg font-bold">
                            📦
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Add Product to
                                Catalog</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono">Auto-derives export SKU acronym, GST tariff
                                & online HSN</p>
                        </div>
                    </div>
                    <button @click="showAddModal = false"
                        class="text-neutral-400 hover:text-white p-1 rounded-lg text-sm cursor-pointer">✕</button>
                </div>

                <form action="{{ route('products.store') }}" method="POST"
                    class="p-4 sm:p-5 space-y-4 text-xs overflow-y-auto flex-1 touch-scroll">
                    @csrf

                    <!-- 1. Product Description & Cut with Auto-derive Trigger -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block font-bold text-neutral-700">Product Description & Cut *</label>
                            <span
                                class="text-[10px] text-[#091315] font-bold bg-[#D7FF53]/30 px-2 py-0.5 rounded-full">⚡
                                Type name for auto HSN & SKU</span>
                        </div>
                        <input type="text" name="product_name" x-model="form.product_name" @input="onProductNameInput()"
                            @change="onProductNameInput()" list="standardCutsList" required
                            placeholder="e.g. Dehydrated Spring Onion / Dehydrated Cabbage Flakes (3-5 mm)"
                            class="w-full px-3.5 py-2.5 border border-neutral-300 rounded-xl font-bold text-neutral-900 focus:ring-2 focus:ring-[#091315] outline-hidden">

                        <!-- Quick Cut Selection Chips with Export Acronyms -->
                        <div class="flex items-center gap-1.5 flex-wrap mt-2">
                            <span class="text-[10px] text-neutral-400 font-bold">Quick Presets:</span>
                            <template x-for="preset in quickCutPresets" :key="preset.code">
                                <button type="button" @click="selectPreset(preset)"
                                    class="px-2.5 py-0.5 rounded-full bg-neutral-100 hover:bg-[#D7FF53] hover:text-[#091315] text-neutral-700 text-[10px] font-bold transition-colors cursor-pointer"
                                    x-text="preset.label"></button>
                            </template>
                        </div>
                    </div>

                    <!-- 2. Export SKU Code & Live HSN Code API Search -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-neutral-700">Export SKU Code</label>
                                <span class="text-[10px] text-emerald-600 font-bold">● Auto-generated</span>
                            </div>
                            <input type="text" name="product_code" x-model="form.product_code"
                                placeholder="DSO / DCAB-F"
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-mono uppercase font-bold text-neutral-900 bg-neutral-50 text-sm focus:ring-2 focus:ring-[#091315]">
                        </div>
                        <div class="relative" @click.outside="isHsnDropdownOpen = false">
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-bold text-neutral-700">HSN Code (GST Tariff) *</label>
                                <button type="button" @click="searchHsnOnline()"
                                    class="text-[10px] text-neutral-900 bg-[#D7FF53]/40 hover:bg-[#D7FF53] px-2 py-0.5 rounded-full font-bold flex items-center gap-0.5 cursor-pointer">
                                    <span>🔍 Live HSN API</span>
                                </button>
                            </div>
                            <div class="relative">
                                <input type="text" name="hsn_code" x-model="form.hsn_code" @input="searchHsnOnline()"
                                    required placeholder="07129020 (Garlic) or 07122000 (Onion)"
                                    class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-mono font-bold text-neutral-900 text-sm focus:ring-2 focus:ring-[#091315]">
                                <div x-show="isSearchingHsn" class="absolute right-3 top-2.5">
                                    <div
                                        class="w-3.5 h-3.5 border-2 border-neutral-900 border-t-transparent rounded-full animate-spin">
                                    </div>
                                </div>
                            </div>

                            <!-- Live HSN API Auto-suggestions Dropdown -->
                            <div x-show="isHsnDropdownOpen && hsnSearchResults.length > 0"
                                class="absolute left-0 right-0 top-full mt-1 bg-white rounded-2xl shadow-xl border border-neutral-200 z-50 p-2 max-h-48 overflow-y-auto space-y-1">
                                <div class="text-[9px] font-bold text-neutral-400 uppercase tracking-wider px-2 py-1">
                                    Verified GST / CBIC Tariff Matches
                                </div>
                                <template x-for="item in hsnSearchResults" :key="item.hsn">
                                    <div @click="applyHsn(item)"
                                        class="p-2 rounded-xl hover:bg-[#F5F6F8] cursor-pointer flex items-start justify-between gap-2">
                                        <div>
                                            <span class="font-mono font-bold text-xs text-neutral-900"
                                                x-text="item.hsn"></span>
                                            <p class="text-[10px] text-neutral-500 leading-tight"
                                                x-text="item.description"></p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span
                                                class="text-[10px] font-extrabold px-1.5 py-0.5 rounded-full bg-[#D7FF53] text-[#091315]"
                                                x-text="item.tax_rate + '% GST'"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Standard Domestic & Export Rates / Packaging -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Standard Market Rate / KG (₹) *</label>
                            <input type="number" step="0.01" name="standard_rate" x-model="form.standard_rate" required
                                placeholder="240.00"
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-bold text-neutral-900 text-sm focus:ring-2 focus:ring-[#091315]">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">GST Tax Rate (%) *</label>
                            <select name="tax_rate_percent" x-model="form.tax_rate_percent"
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-bold text-neutral-900 text-xs bg-white focus:ring-2 focus:ring-[#091315]">
                                <option value="5.00">5% GST (Standard Agricultural Dehydrates)</option>
                                <option value="12.00">12% GST (Processed with Value-Add)</option>
                                <option value="18.00">18% GST (Packaged Retail / Seasoning Blends)</option>
                                <option value="0.00">0% GST (Exempted / Merchant Export SEZ)</option>
                            </select>
                        </div>
                    </div>

                    <!-- 4. Packaging Specs -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="block font-bold text-neutral-700">Export Packaging Specifications</label>
                            <span class="text-[10px] text-neutral-400">Select or custom type</span>
                        </div>

                        <!-- Standard Packaging Preset Dropdown -->
                        <select @change="form.packaging = $event.target.value"
                            class="w-full px-3 py-2 border border-neutral-300 rounded-xl text-xs font-semibold text-neutral-800 bg-white focus:ring-2 focus:ring-[#091315]">
                            <option value="20 KGs Two Poly Liner Bags with Laminated Paper Bag">20 KGs Two Poly Liner
                                Bags with Laminated Paper Bag</option>
                            <option value="25 KGs Two Poly Liner Bags with Laminated Paper Bag">25 KGs Two Poly Liner
                                Bags with Laminated Paper Bag</option>
                            <option value="20 KGs Corrugated Box with Dual Poly Liner">20 KGs Corrugated Box with Dual
                                Poly Liner</option>
                            <option value="25 KGs Corrugated Box with Dual Poly Liner">25 KGs Corrugated Box with Dual
                                Poly Liner</option>
                            <option value="20 KGs White Raffia (HDPE Woven) Bag with Poly Liner">20 KGs White Raffia
                                (HDPE Woven) Bag with Poly Liner</option>
                            <option value="25 KGs White Raffia (HDPE Woven) Bag with Poly Liner">25 KGs White Raffia
                                (HDPE Woven) Bag with Poly Liner</option>
                            <option value="50 KGs White Raffia (HDPE Woven) Bag with Liner">50 KGs White Raffia (HDPE
                                Woven) Bag with Liner</option>
                            <option value="10 KGs Master Carton with Aluminium Foil Pouch">10 KGs Master Carton with
                                Aluminium Foil Pouch</option>
                        </select>

                        <!-- Editable Custom Input -->
                        <input type="text" name="packaging" x-model="form.packaging"
                            placeholder="Custom packaging spec..."
                            class="w-full px-3 py-2 border border-neutral-200 rounded-xl font-medium text-neutral-700 bg-neutral-50 focus:ring-2 focus:ring-[#091315]">
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="showAddModal = false"
                            class="px-4 py-2 text-xs font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <button type="submit"
                            class="px-5 py-2 text-xs font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer border border-[#c8f043]">Save
                            Product</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Edit Product Modal (with HSN API Search) -->
    <template x-teleport="body">
        <div x-show="isEditModalOpen" x-cloak
            class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-md flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="isEditModalOpen = false"
                class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-neutral-200/80 max-w-lg w-full overflow-hidden max-h-[92vh] flex flex-col text-xs">
                <!-- Modal Header -->
                <div class="p-4 sm:p-5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-9 h-9 rounded-2xl bg-[#D7FF53]/20 border border-[#D7FF53]/30 text-[#D7FF53] flex items-center justify-center text-lg font-bold">
                            ✏️
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm tracking-tight text-white font-display">Edit Product Master</h3>
                            <p class="text-[11px] text-[#D7FF53] font-mono"
                                x-text="'Updating: ' + (editProduct ? editProduct.product_name : '')"></p>
                        </div>
                    </div>
                    <button type="button" @click="isEditModalOpen = false"
                        class="text-neutral-400 hover:text-white p-1 rounded-lg text-sm cursor-pointer">✕</button>
                </div>

                <form :action="'/products/' + (editProduct ? editProduct.id : '') + '/update'" method="POST"
                    class="p-4 sm:p-5 space-y-3.5 text-xs overflow-y-auto flex-1 touch-scroll">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-neutral-700 mb-1">Product Description & Cut *</label>
                            <input type="text" name="product_name" x-model="editProduct.product_name" required
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-bold focus:ring-2 focus:ring-[#091315]">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Export SKU Code</label>
                            <input type="text" name="product_code" x-model="editProduct.product_code"
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-mono font-bold uppercase focus:ring-2 focus:ring-[#091315]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">HSN Code (GST Tariff)</label>
                            <input type="text" name="hsn_code" x-model="editProduct.hsn_code"
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-mono font-bold focus:ring-2 focus:ring-[#091315]">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Market Reference Rate / KG (₹)
                                *</label>
                            <input type="number" step="0.01" name="standard_rate" x-model="editProduct.standard_rate"
                                required
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-bold focus:ring-2 focus:ring-[#091315]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">GST Tax Rate (%)</label>
                            <input type="number" step="0.01" name="tax_rate_percent"
                                x-model="editProduct.tax_rate_percent"
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl font-bold focus:ring-2 focus:ring-[#091315]">
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">Packaging Spec</label>
                            <input type="text" name="packaging" x-model="editProduct.packaging"
                                class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:ring-2 focus:ring-[#091315]">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex justify-end gap-2 shrink-0">
                        <button type="button" @click="isEditModalOpen = false"
                            class="px-4 py-2 text-xs font-bold text-neutral-600 hover:bg-neutral-100 rounded-full cursor-pointer">Cancel</button>
                        <button type="submit"
                            class="px-5 py-2 text-xs font-extrabold text-[#091315] bg-[#D7FF53] hover:bg-[#c8f043] rounded-full shadow-2xs cursor-pointer border border-[#c8f043]">Update
                            Product</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script>
    function productsManager() {
    return {
        showAddModal: false,
        isEditModalOpen: false,
        editProduct: {
            id: '',
            product_name: '',
            product_code: '',
            hsn_code: '07129020',
            standard_rate: '',
            tax_rate_percent: '5.00',
            packaging: ''
        },
        isSearchingHsn: false,
        isHsnDropdownOpen: false,
        hsnSearchResults: [],
        hsnDebounceTimer: null,
        form: {
            product_name: '',
            product_code: '',
            hsn_code: '07129020',
            standard_rate: '',
            tax_rate_percent: '5.00',
            packaging: '20 KGs Two Poly Liner Bags with Laminated Paper Bag'
        },
        quickCutPresets: [
            { label: 'Garlic Flakes (DGF)', name: 'Dehydrated Garlic Flakes (A-Grade)', code: 'DGF', hsn: '07129020', rate: '135.00' },
            { label: 'Garlic Powder (DGP-100)', name: 'Dehydrated Garlic Powder (80-100 Mesh)', code: 'DGP-100', hsn: '07129020', rate: '140.00' },
            { label: 'Garlic Minced (DGM)', name: 'Dehydrated Garlic Minced (1-3 mm)', code: 'DGM', hsn: '07129020', rate: '145.00' },
            { label: 'White Onion Flakes (DWOF)', name: 'Dehydrated White Onion Flakes / Kibbled', code: 'DWOF', hsn: '07122000', rate: '95.00' },
            { label: 'White Onion Powder (DWOP-100)', name: 'Dehydrated White Onion Powder (80-100 Mesh)', code: 'DWOP-100', hsn: '07122000', rate: '173.00' },
            { label: 'White Onion Minced (DWOM)', name: 'Dehydrated White Onion Minced (1-3 mm)', code: 'DWOM', hsn: '07122000', rate: '100.00' },
            { label: 'Pink Onion Powder (DPOP-100)', name: 'Dehydrated Pink Onion Powder (80-100 Mesh)', code: 'DPOP-100', hsn: '07122000', rate: '115.00' },
            { label: 'Toasted White Onion Powder (DTWOP-100)', name: 'Dehydrated Toasted White Onion Powder (80-100 Mesh)', code: 'DTWOP-100', hsn: '07122000', rate: '198.00' },
            { label: 'Toasted White Onion Flakes (DTWOF)', name: 'Dehydrated Toasted White Onion Flakes', code: 'DTWOF', hsn: '07122000', rate: '185.00' },
            { label: 'Spring Onion Flakes (DSOF)', name: 'Dehydrated Spring Onion Flakes', code: 'DSOF', hsn: '07122000', rate: '200.00' },
            { label: 'Cabbage Flakes (DCAB-F-3-5)', name: 'Dehydrated Cabbage Flakes (3-5 mm)', code: 'DCAB-F-3-5', hsn: '07129090', rate: '170.00' },
            { label: 'Carrot Flakes (DCAR-F-3-5)', name: 'Dehydrated Carrot Flakes (3-5 mm)', code: 'DCAR-F-3-5', hsn: '07129090', rate: '165.00' },
            { label: 'Ginger Powder (DGIN-P)', name: 'Dehydrated Ginger Powder (Sonth Powder)', code: 'DGIN-P', hsn: '09101210', rate: '220.00' },
        ],
        openAddModal() {
            this.form = {
                product_name: '',
                product_code: '',
                hsn_code: '07129020',
                standard_rate: '',
                tax_rate_percent: '5.00',
                packaging: '20 KGs Two Poly Liner Bags with Laminated Paper Bag'
            };
            this.showAddModal = true;
            this.searchHsnOnline();
        },
        openEditModal(prod) {
            this.editProduct = {
                id: prod.id,
                product_name: prod.product_name,
                product_code: prod.product_code,
                hsn_code: prod.hsn_code || '07129090',
                standard_rate: prod.standard_rate || prod.current_rate_per_kg || '',
                tax_rate_percent: prod.tax_rate_percent || '5.00',
                packaging: prod.packaging || prod.default_packaging || '20 KGs Two Poly Liner Bags with Laminated Paper Bag'
            };
            this.isEditModalOpen = true;
        },
        selectPreset(preset) {
            this.form.product_name = preset.name;
            this.form.product_code = preset.code;
            this.form.hsn_code = preset.hsn;
            this.form.standard_rate = preset.rate;
            this.form.tax_rate_percent = '5.00';
            this.isHsnDropdownOpen = false;
        },
        onProductNameInput() {
            const name = (this.form.product_name || '').toLowerCase().trim();
            if (!name) return;

            // 1. Generate Smart Export Acronym SKU
            this.form.product_code = this.generateSkuClient(name);

            // 2. Query Live HSN Lookup API with debouncing
            clearTimeout(this.hsnDebounceTimer);
            this.hsnDebounceTimer = setTimeout(() => {
                this.searchHsnOnline(name);
            }, 250);
        },
        async searchHsnOnline(query = '') {
            const q = query || this.form.hsn_code || this.form.product_name || '';
            this.isSearchingHsn = true;
            try {
                const res = await fetch(`/api/hsn-lookup?q=${encodeURIComponent(q)}`);
                const data = await res.json();
                if (data && data.results) {
                    this.hsnSearchResults = data.results;
                    if (data.results.length > 0 && (!this.form.hsn_code || this.form.hsn_code === '07129020' || this.form.hsn_code === '071290' || this.form.hsn_code === '071220')) {
                        // Auto-select best match
                        const top = data.results[0];
                        this.form.hsn_code = top.hsn_code;
                        this.form.tax_rate_percent = Number(top.gst_rate).toFixed(2);
                    }
                }
            } catch (err) {
                console.error('HSN lookup error:', err);
            } finally {
                this.isSearchingHsn = false;
            }
        },
        selectHsnItem(item) {
            this.form.hsn_code = item.hsn_code;
            this.form.tax_rate_percent = Number(item.gst_rate).toFixed(2);
            this.isHsnDropdownOpen = false;
        },
        generateSkuClient(name) {
            let sku = 'D';

            if (name.includes('toasted') || name.includes('toast')) sku += 'T';
            else if (name.includes('fried')) sku += 'F';

            if (name.includes('spring onion')) sku += 'SO';
            else if (name.includes('white onion') || (name.includes('white') && name.includes('onion'))) sku += 'WO';
            else if (name.includes('pink onion') || (name.includes('pink') && name.includes('onion'))) sku += 'PO';
            else if (name.includes('red onion') || (name.includes('red') && name.includes('onion'))) sku += 'RO';
            else if (name.includes('garlic')) sku += 'G';
            else if (name.includes('onion')) sku += 'O';
            else if (name.includes('cabbage')) sku = 'DCAB';
            else if (name.includes('carrot')) sku = 'DCAR';
            else if (name.includes('beet')) sku = 'DBEET';
            else if (name.includes('spinach') || name.includes('palak')) sku = 'DSPIN';
            else if (name.includes('tomato')) sku = 'DTOM';
            else if (name.includes('ginger') || name.includes('sonth')) sku = 'DGIN';
            else if (name.includes('green chilli') || name.includes('chilli')) sku = 'DGC';
            else if (name.includes('kasuri methi') || name.includes('methi')) sku = 'DKM';
            else if (name.includes('coriander') || name.includes('dhaniya')) sku = 'DCOR';
            else if (name.includes('mint') || name.includes('pudina')) sku = 'DMINT';

            const isVegetablePrefix = ['DCAB', 'DCAR', 'DBEET', 'DSPIN', 'DTOM', 'DGIN', 'DGC', 'DKM', 'DCOR', 'DMINT'].includes(sku);

            if (name.includes('powder')) {
                sku += isVegetablePrefix ? '-P' : 'P';
            } else if (name.includes('flake') || name.includes('kibbled') || name.includes('clove') || name.includes('leaves')) {
                sku += isVegetablePrefix ? '-F' : 'F';
            } else if (name.includes('minced')) {
                sku += isVegetablePrefix ? '-M' : 'M';
            } else if (name.includes('chopped')) {
                sku += isVegetablePrefix ? '-C' : 'C';
            } else if (name.includes('granule')) {
                sku += isVegetablePrefix ? '-G' : 'G';
            }

            const meshMatch = name.match(/(100|80|60|40)\s*mesh/i);
            const mmMatch = name.match(/([0-9]+)\s*-\s*([0-9]+)\s*mm/i);

            if (meshMatch) {
                sku += '-' + meshMatch[1];
            } else if (mmMatch) {
                sku += '-' + mmMatch[1] + '-' + mmMatch[2];
            } else if (name.includes('100')) {
                sku += '-100';
            }

            return sku.toUpperCase();
        }
    };
}
</script>
@endsection