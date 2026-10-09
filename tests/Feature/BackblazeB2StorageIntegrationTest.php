<?php

use App\Models\BusinessDocument;
use App\Models\Customer;
use App\Models\DocumentVersion;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->tenant = Tenant::first() ?? Tenant::create([
        'name' => 'Acme B2 Storage Tenant',
        'slug' => 'acme-b2-storage-tenant',
        'industry' => 'Food Processing & Exports',
        'plan' => 'Enterprise',
        'currency_code' => 'INR',
        'currency_symbol' => '₹',
        'invoice_prefix' => 'INV-',
        'quotation_prefix' => 'QTN-',
        'po_prefix' => 'PO-',
        'shipment_prefix' => 'SHP-',
    ]);

    $this->user = User::first() ?? User::factory()->create(['tenant_id' => $this->tenant->id]);
    if (! $this->user->tenant_id) {
        $this->user->tenant_id = $this->tenant->id;
        $this->user->save();
    }

    TenantManager::setTenant($this->tenant);
    Storage::fake('public');
    Storage::fake('b2');
});

test('appStorageDisk safely returns b2 when configured or falls back to public', function () {
    // When default is local and no cloud keys
    Config::set('filesystems.default', 'local');
    expect(appStorageDisk())->toBe('public');

    // When default is b2 and keys are configured
    Config::set('filesystems.default', 'b2');
    Config::set('filesystems.disks.b2.key', 'test_b2_key_id');
    expect(appStorageDisk())->toBe('b2');

    // When default is b2 but key is missing
    Config::set('filesystems.disks.b2.key', null);
    expect(appStorageDisk())->toBe('public');
});

test('resolveStorageDiskForFile finds file on b2 or falls back to local public', function () {
    Config::set('filesystems.default', 'b2');
    Config::set('filesystems.disks.b2.key', 'test_b2_key_id');

    // File only in b2
    Storage::disk('b2')->put('documents/test_b2.pdf', 'B2 Content');
    expect(resolveStorageDiskForFile('documents/test_b2.pdf'))->toBe('b2');

    // Legacy file only in local public
    Storage::disk('public')->put('documents/legacy_local.pdf', 'Local Content');
    expect(resolveStorageDiskForFile('documents/legacy_local.pdf'))->toBe('public');
});

test('document preview and download stream seamlessly from b2 disk', function () {
    Config::set('filesystems.default', 'b2');
    Config::set('filesystems.disks.b2.key', 'test_b2_key_id');

    $customer = Customer::firstOrCreate(
        ['tenant_id' => $this->tenant->id, 'company_name' => 'B2 Client Ltd'],
        ['contact_person' => 'Rajesh', 'email' => 'rajesh@b2client.test', 'status' => 'ACTIVE']
    );

    $doc = BusinessDocument::create([
        'tenant_id' => $this->tenant->id,
        'document_type' => 'PO',
        'document_number' => 'PO-B2-001',
        'customer_id' => $customer->id,
        'total_amount' => 150000,
        'status' => 'VERIFIED',
        'current_version' => 1,
    ]);

    $storedPath = 'documents/' . $this->tenant->id . '/doc_b2_001.pdf';
    Storage::disk('b2')->put($storedPath, 'PDF content in Backblaze B2');

    DocumentVersion::create([
        'document_id' => $doc->id,
        'version_number' => 1,
        'file_path' => $storedPath,
        'file_name' => 'PO_B2_001.pdf',
        'file_size' => 28,
        'mime_type' => 'application/pdf',
        'is_active' => true,
    ]);

    // Preview
    $previewResponse = $this->actingAs($this->user)->get(route('documents.preview', $doc->id));
    $previewResponse->assertOk();
    $previewResponse->assertHeader('Content-Type', 'application/pdf');

    // Download
    $downloadResponse = $this->actingAs($this->user)->get(route('documents.download', $doc->id));
    $downloadResponse->assertOk();
});

test('storage:migrate-to-b2 command validates credentials and dry-run preview', function () {
    // When credentials missing
    Config::set('filesystems.disks.b2.key', null);
    $this->artisan('storage:migrate-to-b2 --target-disk=b2')
        ->expectsOutputToContain('Cannot connect to cloud disk [b2]: credentials missing.')
        ->assertExitCode(1);

    // When credentials configured
    Config::set('filesystems.disks.b2.key', 'test_key');
    Config::set('filesystems.disks.b2.bucket', 'test_bucket');

    $this->artisan('storage:migrate-to-b2 --target-disk=b2 --dry-run')
        ->assertExitCode(0);
});
