<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MigrateStorageToB2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:migrate-to-b2
                            {--target-disk= : Destination cloud disk (default: b2 or s3)}
                            {--dry-run : Preview files that would be uploaded without migrating}
                            {--cleanup : Safely remove local copy after successful cloud upload}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Non-destructively migrate local stored files (invoices, POs, LRs, photos, media) up to Backblaze B2 Cloud Storage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("=================================================");
        $this->info("  ERPSaaS -> Backblaze B2 Cloud Storage Migration ");
        $this->info("=================================================");

        $isDryRun = $this->option('dry-run');
        $cleanup = $this->option('cleanup');
        $targetDisk = $this->option('target-disk') ?: (config('filesystems.default') === 'b2' ? 'b2' : (config('filesystems.default') === 's3' ? 's3' : 'b2'));

        $this->line("Target Storage Disk : <fg=cyan>{$targetDisk}</>");
        $this->line("Dry Run Mode        : " . ($isDryRun ? "<fg=yellow>ENABLED (no files will be uploaded)</>" : "<fg=green>DISABLED (live upload)</>"));
        $this->line("Cleanup Local Files : " . ($cleanup ? "<fg=red>ENABLED (local copies will be deleted)</>" : "<fg=green>DISABLED (local files preserved)</>"));

        // Validate target disk credentials
        $key = config("filesystems.disks.{$targetDisk}.key");
        $bucket = config("filesystems.disks.{$targetDisk}.bucket");

        if (empty($key) || empty($bucket)) {
            $this->error("Cannot connect to cloud disk [{$targetDisk}]: credentials missing.");
            $this->warn("Please ensure the following environment variables are set in .env:");
            $this->line("  B2_APPLICATION_KEY_ID=<your-b2-key-id>");
            $this->line("  B2_APPLICATION_KEY=<your-b2-application-key>");
            $this->line("  B2_BUCKET=<your-b2-bucket-name>");
            $this->line("  B2_ENDPOINT=<your-b2-endpoint, e.g. https://s3.us-west-004.backblazeb2.com>");
            return self::FAILURE;
        }

        $localRoot = storage_path('app/public');
        if (!File::exists($localRoot)) {
            $this->warn("Local storage directory [{$localRoot}] does not exist. Nothing to migrate.");
            return self::SUCCESS;
        }

        $allFiles = File::allFiles($localRoot);
        // Exclude system files like .gitignore
        $filesToMigrate = array_filter($allFiles, function ($file) {
            return !in_array($file->getFilename(), ['.gitignore', '.DS_Store', 'thumbs.db']);
        });

        $totalFiles = count($filesToMigrate);
        if ($totalFiles === 0) {
            $this->info("No stored files found in [{$localRoot}]. Everything is clean.");
            return self::SUCCESS;
        }

        $this->info("Discovered {$totalFiles} local file(s) to process.\n");

        $uploadedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $totalBytes = 0;

        $bar = $this->output->createProgressBar($totalFiles);
        $bar->start();

        foreach ($filesToMigrate as $file) {
            $fullPath = $file->getRealPath();
            $relativePath = str_replace('\\', '/', substr($fullPath, strlen($localRoot) + 1));
            $fileSize = $file->getSize();

            try {
                // Check if already in B2
                if (Storage::disk($targetDisk)->exists($relativePath)) {
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                if ($isDryRun) {
                    $uploadedCount++;
                    $totalBytes += $fileSize;
                    $bar->advance();
                    continue;
                }

                // Upload using stream to conserve memory
                $stream = fopen($fullPath, 'r');
                $isLogo = str_starts_with($relativePath, 'logos/');
                $visibility = $isLogo ? 'public' : 'private';

                $success = Storage::disk($targetDisk)->put($relativePath, $stream, [
                    'visibility' => $visibility,
                ]);

                if (is_resource($stream)) {
                    fclose($stream);
                }

                if ($success) {
                    $uploadedCount++;
                    $totalBytes += $fileSize;

                    if ($cleanup) {
                        @unlink($fullPath);
                    }
                } else {
                    $failedCount++;
                    $this->error("\nFailed to upload: {$relativePath}");
                }
            } catch (\Throwable $e) {
                $failedCount++;
                $this->error("\nError uploading [{$relativePath}]: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line("\n");

        $formattedBytes = number_format($totalBytes / (1024 * 1024), 2) . ' MB';

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Files Scanned', $totalFiles],
                [$isDryRun ? 'Files To Upload' : 'Uploaded Successfully', $uploadedCount . " ({$formattedBytes})"],
                ['Already Existed in B2 (Skipped)', $skippedCount],
                ['Failed Files', $failedCount],
            ]
        );

        if ($failedCount > 0) {
            $this->warn("Migration finished with {$failedCount} failure(s). Check output above for details.");
            return self::FAILURE;
        }

        $this->info("✓ Migration process completed successfully! All files in Backblaze B2 are safe and accessible.");
        return self::SUCCESS;
    }
}
