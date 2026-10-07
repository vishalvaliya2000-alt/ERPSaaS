<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CreditDebitNoteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductDocumentController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TransporterController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/cache-clear', function () {
    Artisan::call('optimize:clear');
    Artisan::call('config:cache');
    Artisan::call('route:cache');
    Artisan::call('view:cache');
    Artisan::call('event:cache');

    return redirect()->route('dashboard')->with('success', 'Cache cleared!');
});

// -------------------------------------------------------------
// AUTHENTICATED ERP ROUTES (Fortify handles /login, /register, /logout, /two-factor-challenge natively)
// -------------------------------------------------------------
Route::middleware('auth')->group(function () {

    // User Profile Security & Settings
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');

    // 1. Dashboard / Action-First Daily Command Center
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');
    Route::post('/dashboard/generate-actions', [DashboardController::class, 'generateActions'])->name('dashboard.generate-actions');
    Route::get('/dashboard/daily-digest', [DashboardController::class, 'dailyDigest'])->name('dashboard.daily-digest');

    // 2. Customer 360 (Create, Show, Edit, Delete)
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{id}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{id}/update', [CustomerController::class, 'update'])->name('customers.update');
    Route::post('/customers/{id}/delete', [CustomerController::class, 'destroy'])->name('customers.destroy');

    // 3. CRM / Sales Pipeline & Conversion
    Route::get('/pipeline', [PipelineController::class, 'index'])->name('pipeline.index');
    Route::post('/pipeline', [PipelineController::class, 'store'])->name('pipeline.store');
    Route::post('/pipeline/{id}/stage', [PipelineController::class, 'updateStage'])->name('pipeline.stage');
    Route::post('/pipeline/{id}/activity', [PipelineController::class, 'logActivity'])->name('pipeline.activity');
    Route::post('/pipeline/{id}/convert', [PipelineController::class, 'convertToCustomer'])->name('pipeline.convert');

    // 4. Samples Lifecycle & Evaluation (Create, Edit, Delete)
    Route::get('/samples', [SampleController::class, 'index'])->name('samples.index');
    Route::post('/samples', [SampleController::class, 'store'])->name('samples.store');
    Route::post('/samples/{id}/status', [SampleController::class, 'updateStatus'])->name('samples.status');
    Route::post('/samples/{id}/update', [SampleController::class, 'update'])->name('samples.update');
    Route::post('/samples/{id}/delete', [SampleController::class, 'destroy'])->name('samples.destroy');

    // 5. Quotation Generator (Create, Show, Edit, Delete)
    Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
    Route::post('/quotations', [QuotationController::class, 'store'])->name('quotations.store');
    Route::get('/quotations/{id}', [QuotationController::class, 'show'])->name('quotations.show');
    Route::post('/quotations/{id}/update', [QuotationController::class, 'update'])->name('quotations.update');
    Route::post('/quotations/{id}/delete', [QuotationController::class, 'destroy'])->name('quotations.destroy');
    Route::post('/quotations/{id}/convert', [QuotationController::class, 'convert'])->name('quotations.convert');

    // 6. Products Master (Create, Edit, Delete, HSN Lookup, Documents & Media)
    Route::get('/api/hsn-lookup', [ProductController::class, 'hsnLookup'])->name('api.hsn.lookup');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');
    Route::post('/products/{id}/update', [ProductController::class, 'update'])->name('products.update');
    Route::post('/products/{id}/delete', [ProductController::class, 'destroy'])->name('products.destroy');

    // Product Documents & Media Management
    Route::get('/api/products/{id}/documents', [ProductDocumentController::class, 'getDocuments'])->name('products.documents.api');
    Route::post('/products/{id}/photos/upload', [ProductDocumentController::class, 'uploadPhotos'])->name('products.photos.upload');
    Route::post('/products/{id}/documents/upload', [ProductDocumentController::class, 'uploadDocument'])->name('products.documents.upload');
    Route::post('/products/{id}/photos/{docId}/set-primary', [ProductDocumentController::class, 'setPrimaryPhoto'])->name('products.photos.set-primary');
    Route::post('/products/{id}/photos/reorder', [ProductDocumentController::class, 'reorderPhotos'])->name('products.photos.reorder');
    Route::post('/products/{id}/documents/{docId}/toggle-latest', [ProductDocumentController::class, 'toggleLatest'])->name('products.documents.toggle-latest');
    Route::get('/products/{productId}/documents/{docId}/preview', [ProductDocumentController::class, 'preview'])->name('products.documents.preview');
    Route::get('/products/{productId}/documents/{docId}/download', [ProductDocumentController::class, 'download'])->name('products.documents.download');
    Route::get('/products/{productId}/documents/download-all', [ProductDocumentController::class, 'downloadAllZip'])->name('products.documents.download-all');
    Route::post('/products/{productId}/documents/{docId}/delete', [ProductDocumentController::class, 'destroy'])->name('products.documents.destroy');
    Route::delete('/products/{productId}/documents/{docId}', [ProductDocumentController::class, 'destroy'])->name('products.documents.delete');

    // Document Sharing API
    Route::post('/products/{productId}/documents/share', [ProductDocumentController::class, 'createShare'])->name('products.documents.share.create');
    Route::delete('/products/{productId}/documents/share/{shareId}', [ProductDocumentController::class, 'deleteShare'])->name('products.documents.share.delete');

    // 7. Sales Orders & Partial Dispatches (Create, Edit, Delete, Revisions)
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{id}/revisions', [OrderController::class, 'revisions'])->name('orders.revisions');
    Route::post('/orders/{id}/update', [OrderController::class, 'update'])->name('orders.update');
    Route::post('/orders/{id}/advance', [OrderController::class, 'recordAdvance'])->name('orders.advance');
    Route::post('/orders/{id}/delete', [OrderController::class, 'destroy'])->name('orders.destroy');

    // 8. Commercial Transport & LR Tracking (Create with Multi-PO, LR Upload, Edit, Delete)
    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::post('/shipments', [ShipmentController::class, 'store'])->name('shipments.store');
    Route::get('/shipments/{id}/live-tracking', [ShipmentController::class, 'liveTracking'])->name('shipments.live-tracking');
    Route::get('/api/transporter/tracking', [ShipmentController::class, 'trackConsignmentApi'])->name('shipments.track-api');
    Route::post('/shipments/{id}/update', [ShipmentController::class, 'update'])->name('shipments.update');
    Route::post('/shipments/{id}/status', [ShipmentController::class, 'updateStatus'])->name('shipments.status');
    Route::post('/shipments/{id}/delete', [ShipmentController::class, 'destroy'])->name('shipments.destroy');

    // 9. Invoices & AR Ledger (Create, Edit, Delete with Payment Protection)
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{id}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('/invoices/{id}/update', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::post('/invoices/{id}/delete', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::post('/invoices/receipt', [InvoiceController::class, 'storeReceipt'])->name('invoices.receipt');

    // Credit Notes & Debit Notes (Sales Returns, Shortages, Rate Revisions)
    Route::get('/credit-debit-notes', [CreditDebitNoteController::class, 'index'])->name('credit-debit-notes.index');
    Route::post('/credit-debit-notes', [CreditDebitNoteController::class, 'store'])->name('credit-debit-notes.store');
    Route::get('/credit-debit-notes/{id}', [CreditDebitNoteController::class, 'show'])->name('credit-debit-notes.show');
    Route::get('/credit-debit-notes/{id}/print', [CreditDebitNoteController::class, 'print'])->name('credit-debit-notes.print');
    Route::post('/credit-debit-notes/{id}/cancel', [CreditDebitNoteController::class, 'cancel'])->name('credit-debit-notes.cancel');
    Route::get('/api/invoices/{id}/details', [CreditDebitNoteController::class, 'apiGetInvoice'])->name('api.invoices.details');

    // Document File Handling (Direct View & Download from Transactions)
    Route::redirect('/documents', '/')->name('documents.index');
    Route::get('/documents/check-duplicate', [DocumentController::class, 'checkDuplicate'])->name('documents.check-duplicate');
    Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{id}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::post('/documents/{id}/delete', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // 10. Vendors & Suppliers (Raw Material & Packaging)
    Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
    Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
    Route::post('/vendors/{id}/update', [VendorController::class, 'update'])->name('vendors.update');
    Route::post('/vendors/{id}/delete', [VendorController::class, 'destroy'])->name('vendors.destroy');

    // 11. Transporters Directory (Freight Carriers & Logistics)
    Route::get('/transporters', [TransporterController::class, 'index'])->name('transporters.index');
    Route::post('/transporters', [TransporterController::class, 'store'])->name('transporters.store');
    Route::post('/transporters/{id}/update', [TransporterController::class, 'update'])->name('transporters.update');
    Route::post('/transporters/{id}/delete', [TransporterController::class, 'destroy'])->name('transporters.destroy');

    // 12. Follow-up Tasks & Next Action Engine
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('/tasks/{id}/complete', [TaskController::class, 'complete'])->name('tasks.complete');

    // 13. Production & Quality Batches
    Route::get('/production', [ProductionController::class, 'index'])->name('production.index');
    Route::post('/production/batch', [ProductionController::class, 'storeBatch'])->name('production.batch.store');

    // 14. AI Natural Language Assistant
    Route::get('/assistant', [AssistantController::class, 'index'])->name('assistant.index');
    Route::post('/assistant/query', [AssistantController::class, 'query'])->name('assistant.query');

    // 15. Excel Migration & Export Hub
    Route::get('/excel', [ExcelController::class, 'index'])->name('excel.index');

    // 16. Organization & Team Settings (Strictly Isolated to User's Company)
    Route::get('/organization/settings', [TenantController::class, 'settings'])->name('organization.settings');
    Route::post('/organization/settings', [TenantController::class, 'updateSettings'])->name('organization.settings.update');
    Route::get('/organization/team', [TenantController::class, 'team'])->name('organization.team');
    Route::post('/organization/team/invite', [TenantController::class, 'inviteMember'])->name('organization.team.invite');
    Route::post('/organization/team/{userId}/remove', [TenantController::class, 'removeMember'])->name('organization.team.remove');

    Route::get('/excel/export', [ExcelController::class, 'export'])->name('excel.export');

    // 13. Global Search API
    Route::get('/search', [SearchController::class, 'search'])->name('search');
});

// Public Document Sharing Portal (No Authentication Required, Optional PIN)
Route::get('/shared/products/{shareToken}', [ProductDocumentController::class, 'viewShared'])->name('products.documents.shared');
Route::post('/shared/products/{shareToken}/verify', [ProductDocumentController::class, 'verifySharePassword'])->name('products.documents.shared.verify');
Route::get('/shared/products/{shareToken}/download/{docId}', [ProductDocumentController::class, 'downloadSharedDocument'])->name('products.documents.shared.download');
Route::get('/shared/products/{shareToken}/preview/{docId}', [ProductDocumentController::class, 'previewSharedDocument'])->name('products.documents.shared.preview');
Route::get('/shared/products/{shareToken}/download-all', [ProductDocumentController::class, 'downloadSharedZip'])->name('products.documents.shared.download-all');

