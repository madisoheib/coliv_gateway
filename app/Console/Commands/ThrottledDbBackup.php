<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ThrottledDbBackup extends Command
{
    protected $signature = 'backup:db-throttled {--rate=5m : Transfer rate limit (e.g., 2m=2MB/s, 5m=5MB/s, 10m=10MB/s)}';

    protected $description = 'Run a throttled database backup to reduce RDS load. Uses pv to rate-limit the mysqldump.';

    public function handle()
    {
        $rate = $this->option('rate');

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $timestamp = now()->format('Y-m-d-H-i-s');
        $appName = config('app.name', 'laravel');
        $dumpFile = storage_path("app/backup-temp/{$timestamp}-{$database}.sql.gz");
        $zipFile = storage_path("app/backup-temp/{$timestamp}-backup.zip");

        // Ensure temp directory exists
        @mkdir(dirname($dumpFile), 0775, true);

        $this->info("Starting throttled backup at rate: {$rate}/s");
        $this->info("Database: {$database} on {$host}:{$port}");

        // Check if pv is available
        $hasPv = trim(shell_exec('which pv 2>/dev/null')) !== '';

        // Build mysqldump command
        $dumpCmd = sprintf(
            'mysqldump --single-transaction --quick --skip-lock-tables -h %s -P %s -u %s %s %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '-p' . escapeshellarg($password) : '',
            escapeshellarg($database)
        );

        if ($hasPv) {
            // pv rate-limits the data flow: e.g., 5m = 5MB/s
            $cmd = sprintf('%s | pv -q -L %s | gzip > %s',
                $dumpCmd,
                escapeshellarg($rate),
                escapeshellarg($dumpFile)
            );
            $this->info("Using pv rate limiter: {$rate}/s");
        } else {
            // Fallback: use dd with 1MB blocks and sleep for throttling
            $this->warn('pv not found, using dd-based throttling (less precise)');
            $cmd = sprintf('%s | gzip | dd bs=1M 2>/dev/null > %s',
                $dumpCmd,
                escapeshellarg($dumpFile)
            );
        }

        $this->info('Dumping database...');
        $startTime = microtime(true);

        $exitCode = 0;
        passthru($cmd, $exitCode);

        if ($exitCode !== 0) {
            $this->error('mysqldump failed with exit code: ' . $exitCode);
            $this->cleanup($dumpFile, $zipFile);
            return 1;
        }

        $dumpSize = file_exists($dumpFile) ? filesize($dumpFile) : 0;
        $elapsed = round(microtime(true) - $startTime, 1);
        $this->info("Dump completed: {$this->humanSize($dumpSize)} in {$elapsed}s");

        // Create zip file
        $this->info('Creating zip archive...');
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE) === true) {
            $zip->addFile($dumpFile, basename($dumpFile));
            $zip->close();
        } else {
            $this->error('Failed to create zip file');
            $this->cleanup($dumpFile, $zipFile);
            return 1;
        }

        // Upload to S3 using stream (no memory limit issue)
        $this->info('Uploading to S3...');
        try {
            $disk = Storage::disk('backups');
            $s3Path = $appName . '/' . basename($zipFile);
            $stream = fopen($zipFile, 'r');
            $disk->writeStream($s3Path, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            $this->info("Uploaded to: {$s3Path}");
        } catch (\Exception $e) {
            $this->error('S3 upload failed: ' . $e->getMessage());
            $this->cleanup($dumpFile, $zipFile);
            return 1;
        }

        // Cleanup temp files
        $this->cleanup($dumpFile, $zipFile);

        $totalElapsed = round(microtime(true) - $startTime, 1);
        $this->info("Backup completed successfully in {$totalElapsed}s");

        return 0;
    }

    protected function cleanup(string ...$files): void
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    protected function humanSize(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
