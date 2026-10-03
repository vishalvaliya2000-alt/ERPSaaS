<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F5F6F8]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <title>{{ $share->title ?: ($product->product_code . ' Documentation') }} — ERPSaaS</title>

    <!-- Google Fonts: Outfit, Plus Jakarta Sans, IBM Plex Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Outfit"', '"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['"Outfit"', 'sans-serif'],
                        mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
                    },
                    colors: {
                        brand: {
                            DEFAULT: '#091315',
                            accent: '#D7FF53',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        ::selection { background-color: #D7FF53; color: #091315; }
    </style>
</head>
<body class="font-sans text-neutral-900 bg-[#F5F6F8] min-h-screen flex flex-col selection:bg-[#D7FF53] selection:text-[#091315]"
    x-data="{
        previewModalOpen: false,
        previewDoc: null,
        previewType: '',
        openPreview(url, name, mime) {
            this.previewDoc = { url, name, mime };
            this.previewType = (mime && mime.includes('pdf')) ? 'pdf' : ((mime && mime.includes('image')) ? 'image' : 'frame');
            this.previewModalOpen = true;
        }
    }">

    <!-- Top Navigation Banner -->
    <header class="bg-[#091315] text-white border-b border-neutral-800 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-[#D7FF53] text-[#091315] flex items-center justify-center font-black text-lg shadow-sm">
                    📦
                </div>
                <div>
                    <span class="text-sm font-black tracking-tight text-white block font-display">ERPSaaS Documentation Portal</span>
                    <span class="text-[10px] font-mono tracking-widest text-[#D7FF53] uppercase block font-semibold">Verified Client Dossier</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if($photos->count() + $documents->count() > 0)
                <a href="{{ route('products.documents.shared.download-all', $share->share_token) }}"
                    class="px-4 py-2 bg-[#D7FF53] hover:bg-[#c8f043] text-[#091315] font-black text-xs rounded-full shadow-sm transition-all flex items-center gap-2 border border-[#c8f043]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 12L12 16.5m0 0L16.5 12M12 16.5V3" />
                    </svg>
                    <span>Download All (ZIP)</span>
                </a>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">
        
        <!-- Product & Dossier Hero Card -->
        <div class="bg-white rounded-3xl border border-neutral-200/80 p-5 sm:p-7 shadow-xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span class="font-mono text-xs font-black px-2.5 py-1 rounded-lg bg-neutral-100 text-neutral-800 border border-neutral-200">
                            {{ $product->product_code }}
                        </span>
                        <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                            {{ $product->category?->name ?? 'Export Catalog' }}
                        </span>
                        @if($product->hsn_code)
                        <span class="font-mono text-xs font-semibold text-neutral-500">
                            HSN: <span class="text-neutral-800 font-bold">{{ $product->hsn_code }}</span>
                        </span>
                        @endif
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-neutral-900 font-display tracking-tight">
                        {{ $product->product_name }}
                    </h1>
                    @if($product->packaging)
                    <p class="text-xs text-neutral-500 flex items-center gap-1.5">
                        <span class="text-neutral-400">Standard Packaging:</span>
                        <span class="font-medium text-neutral-700">{{ $product->packaging }}</span>
                    </p>
                    @endif
                </div>

                <!-- Share Status Metadata -->
                <div class="flex flex-col sm:flex-row md:flex-col items-start sm:items-center md:items-end gap-2 text-xs">
                    @if($share->expires_at)
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 text-amber-800 border border-amber-200/70 font-medium">
                        <span>⏳</span>
                        <span>Access Valid Until: <b class="font-mono">{{ $share->expires_at->format('d M Y, h:i A') }}</b></span>
                    </div>
                    @else
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-neutral-100 text-neutral-700 border border-neutral-200 font-medium">
                        <span>🔒</span>
                        <span>Direct Access Link</span>
                    </div>
                    @endif

                    <div class="text-[11px] text-neutral-400 font-mono">
                        {{ $photos->count() }} Photo{{ $photos->count() === 1 ? '' : 's' }} • {{ $documents->count() }} Technical Document{{ $documents->count() === 1 ? '' : 's' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 1: Product Photos -->
        @if($photos->count() > 0)
        <div class="bg-white rounded-3xl border border-neutral-200/80 p-5 sm:p-7 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📸</span>
                    <div>
                        <h2 class="font-black text-base text-neutral-900 font-display">Product Photos & Visuals</h2>
                        <p class="text-[11px] text-neutral-500">High-resolution physical inspection images, mesh cuts, and packaging</p>
                    </div>
                </div>
                <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-md bg-neutral-100 text-neutral-700">
                    {{ $photos->count() }} item{{ $photos->count() === 1 ? '' : 's' }}
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                @foreach($photos as $photo)
                @php
                    $previewUrl = route('products.documents.shared.preview', ['shareToken' => $share->share_token, 'docId' => $photo->id]);
                    $downloadUrl = route('products.documents.shared.download', ['shareToken' => $share->share_token, 'docId' => $photo->id]);
                @endphp
                <div class="group relative bg-neutral-50 rounded-2xl border border-neutral-200 overflow-hidden hover:border-neutral-400 transition-all flex flex-col">
                    <div class="aspect-square w-full bg-neutral-100 relative overflow-hidden flex items-center justify-center cursor-pointer"
                        @click="openPreview('{{ $previewUrl }}', '{{ addslashes($photo->file_name) }}', '{{ $photo->mime_type }}')">
                        <img src="{{ $previewUrl }}" alt="{{ $photo->file_name }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @if($photo->is_primary)
                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-[#091315] text-[#D7FF53] text-[9px] font-black uppercase tracking-wider font-mono shadow-xs border border-[#D7FF53]/30">
                            ★ Primary
                        </span>
                        @endif
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 text-white">
                            <span class="text-xs font-bold px-3 py-1 rounded-full bg-black/60 backdrop-blur-xs">🔍 Click to View</span>
                        </div>
                    </div>

                    <div class="p-2.5 text-xs flex items-center justify-between gap-1 bg-white border-t border-neutral-100 mt-auto">
                        <div class="truncate">
                            <span class="font-bold text-neutral-800 block truncate text-[11px]">{{ $photo->file_name }}</span>
                            <span class="text-[10px] text-neutral-400 font-mono">{{ $photo->formatted_file_size }}</span>
                        </div>
                        <a href="{{ $downloadUrl }}"
                            title="Download Photo"
                            class="p-1.5 rounded-lg bg-neutral-100 hover:bg-neutral-200 text-neutral-700 transition-colors shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 12L12 16.5m0 0L16.5 12M12 16.5V3" />
                            </svg>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Section 2: Technical Specifications & Certificates -->
        <div class="bg-white rounded-3xl border border-neutral-200/80 p-5 sm:p-7 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📑</span>
                    <div>
                        <h2 class="font-black text-base text-neutral-900 font-display">Technical Documents & Regulatory Certificates</h2>
                        <p class="text-[11px] text-neutral-500">Official Certificate of Analysis (COA), Technical Data Sheets (TDS), and MSDS</p>
                    </div>
                </div>
                <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-md bg-neutral-100 text-neutral-700">
                    {{ $documents->count() }} document{{ $documents->count() === 1 ? '' : 's' }}
                </span>
            </div>

            @if($documents->isEmpty())
            <div class="py-12 text-center text-neutral-400 text-xs">
                No technical documents attached to this share.
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-neutral-50 text-neutral-600 font-bold border-b border-neutral-200/80">
                            <th class="p-3.5">Document Type</th>
                            <th class="p-3.5">File & Description</th>
                            <th class="p-3.5 text-center">Version</th>
                            <th class="p-3.5">Validity / Expiry</th>
                            <th class="p-3.5 text-right">Size</th>
                            <th class="p-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach($documents as $doc)
                        @php
                            $previewUrl = route('products.documents.shared.preview', ['shareToken' => $share->share_token, 'docId' => $doc->id]);
                            $downloadUrl = route('products.documents.shared.download', ['shareToken' => $share->share_token, 'docId' => $doc->id]);
                        @endphp
                        <tr class="hover:bg-neutral-50/70 transition-colors">
                            <td class="p-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $doc->type_badge_class }}">
                                    <span>{{ $doc->type_icon }}</span>
                                    <span>{{ $doc->type_label }}</span>
                                </span>
                            </td>
                            <td class="p-3.5">
                                <div class="font-bold text-neutral-900 text-sm flex items-center gap-2">
                                    <span>{{ $doc->file_name }}</span>
                                    @if($doc->is_latest)
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase font-mono tracking-wide">
                                        LATEST
                                    </span>
                                    @endif
                                </div>
                                @if($doc->notes)
                                <div class="text-[11px] text-neutral-500 mt-0.5 italic">
                                    {{ $doc->notes }}
                                </div>
                                @endif
                            </td>
                            <td class="p-3.5 text-center whitespace-nowrap font-mono font-bold text-neutral-700">
                                {{ $doc->version ?: '—' }}
                            </td>
                            <td class="p-3.5 whitespace-nowrap">
                                @if($doc->valid_until)
                                    @if($doc->is_expired)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold text-[11px]">
                                        <span>⚠️</span>
                                        <span>Expired ({{ $doc->valid_until->format('d M Y') }})</span>
                                    </span>
                                    @elseif($doc->days_until_expiry !== null && $doc->days_until_expiry <= 30)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[11px]">
                                        <span>⏳</span>
                                        <span>Expires in {{ $doc->days_until_expiry }}d ({{ $doc->valid_until->format('d M Y') }})</span>
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-bold text-[11px]">
                                        <span>✓</span>
                                        <span>Valid until {{ $doc->valid_until->format('d M Y') }}</span>
                                    </span>
                                    @endif
                                @else
                                    <span class="text-neutral-400 font-mono">No Expiry</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-right font-mono text-neutral-500 whitespace-nowrap">
                                {{ $doc->formatted_file_size }}
                            </td>
                            <td class="p-3.5 text-right whitespace-nowrap space-x-1.5">
                                <button type="button"
                                    @click="openPreview('{{ $previewUrl }}', '{{ addslashes($doc->file_name) }}', '{{ $doc->mime_type }}')"
                                    class="px-3 py-1.5 rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-800 font-bold text-xs inline-flex items-center gap-1 transition-colors cursor-pointer">
                                    <span>👁️</span>
                                    <span>Preview</span>
                                </button>
                                <a href="{{ $downloadUrl }}"
                                    class="px-3 py-1.5 rounded-xl bg-[#091315] hover:bg-black text-[#D7FF53] font-bold text-xs inline-flex items-center gap-1 transition-colors cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 12L12 16.5m0 0L16.5 12M12 16.5V3" />
                                    </svg>
                                    <span>Download</span>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-auto py-6 border-t border-neutral-200/80 bg-white text-center text-xs text-neutral-400">
        <p>Verified documentation dossier powered by <span class="font-bold text-neutral-800">ERPSaaS Operations Platform</span>.</p>
    </footer>

    <!-- Inline Document & Image Preview Modal -->
    <template x-teleport="body">
        <div x-show="previewModalOpen" x-cloak
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-2 sm:p-4">
            <div @click.outside="previewModalOpen = false"
                class="bg-white rounded-3xl shadow-2xl border border-neutral-800 w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden">
                <!-- Preview Header -->
                <div class="px-5 py-3.5 bg-[#091315] text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5 truncate">
                        <span class="text-lg">📄</span>
                        <h3 class="font-bold text-sm text-white truncate font-display" x-text="previewDoc ? previewDoc.name : 'Document Preview'"></h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <a :href="previewDoc ? previewDoc.url : '#'" download
                            class="px-3 py-1 bg-[#D7FF53] text-[#091315] font-black text-xs rounded-xl hover:bg-[#c8f043] transition-colors flex items-center gap-1.5">
                            <span>⬇</span>
                            <span>Download</span>
                        </a>
                        <button type="button" @click="previewModalOpen = false"
                            class="text-neutral-400 hover:text-white p-1 rounded-lg text-lg cursor-pointer">✕</button>
                    </div>
                </div>

                <!-- Preview Content -->
                <div class="flex-1 bg-neutral-100 relative overflow-hidden flex items-center justify-center p-2">
                    <template x-if="previewType === 'image'">
                        <img :src="previewDoc.url" :alt="previewDoc.name" class="max-h-full max-w-full object-contain rounded-xl shadow-md">
                    </template>
                    <template x-if="previewType === 'pdf' || previewType === 'frame'">
                        <iframe :src="previewDoc.url" class="w-full h-full rounded-xl border border-neutral-200 bg-white"></iframe>
                    </template>
                </div>
            </div>
        </div>
    </template>
</body>
</html>
