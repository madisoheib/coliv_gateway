<?php

namespace App\Http\Controllers;

use App\Models\BackupSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function index()
    {
        $disk = $this->getBackupDisk();
        $backups = [];
        $totalSize = 0;

        if ($disk) {
            try {
                $files = $disk->allFiles('');
                foreach ($files as $file) {
                    if (str_ends_with($file, '.zip')) {
                        $size = $disk->size($file);
                        $lastModified = $disk->lastModified($file);
                        $totalSize += $size;
                        $backups[] = [
                            'filename' => $file,
                            'basename' => basename($file),
                            'size' => $size,
                            'size_human' => $this->humanFileSize($size),
                            'date' => date('Y-m-d H:i:s', $lastModified),
                            'timestamp' => $lastModified,
                        ];
                    }
                }
                usort($backups, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);
            } catch (\Exception $e) {
                session()->flash('error', 'Could not connect to backup storage: ' . $e->getMessage());
            }
        }

        $scheduleEnabled = BackupSetting::get('schedule_enabled', false);
        $scheduleFrequency = BackupSetting::get('schedule_frequency', 'daily');
        $scheduleTime = BackupSetting::get('schedule_time', '01:30');

        return view('backup.index', [
            'backups' => $backups,
            'totalSize' => $this->humanFileSize($totalSize),
            'backupCount' => count($backups),
            'lastBackup' => $backups[0] ?? null,
            'scheduleEnabled' => $scheduleEnabled,
            'scheduleFrequency' => $scheduleFrequency,
            'scheduleTime' => $scheduleTime,
        ]);
    }

    public function runBackup()
    {
        $statusFile = storage_path('app/backup-status.json');

        // Check if a backup is already running
        if (file_exists($statusFile)) {
            $status = json_decode(file_get_contents($statusFile), true);
            if (($status['status'] ?? '') === 'running') {
                return back()->with('error', 'A backup is already running.');
            }
        }

        // Write initial status
        file_put_contents($statusFile, json_encode([
            'status' => 'running',
            'started_at' => now()->toDateTimeString(),
            'message' => 'Starting database backup...',
        ]));

        // Launch backup in background — use 'php' CLI (PHP_BINARY may point to php-fpm)
        $php = trim(shell_exec('which php') ?: 'php');
        $artisan = base_path('artisan');
        $cmd = sprintf(
            'nice -n 19 ionice -c2 -n7 %s %s backup:db-throttled --rate=5m > %s 2>&1 & echo $!',
            escapeshellarg($php),
            escapeshellarg($artisan),
            escapeshellarg(storage_path('app/backup-output.log'))
        );

        $pid = trim(shell_exec($cmd));

        // Store PID
        file_put_contents($statusFile, json_encode([
            'status' => 'running',
            'pid' => (int) $pid,
            'started_at' => now()->toDateTimeString(),
            'message' => 'Database backup started...',
        ]));

        return back()->with('success', 'Backup started in the background. You can track progress below.');
    }

    public function backupStatus()
    {
        $statusFile = storage_path('app/backup-status.json');
        $outputFile = storage_path('app/backup-output.log');

        if (! file_exists($statusFile)) {
            return response()->json(['status' => 'idle']);
        }

        $status = json_decode(file_get_contents($statusFile), true);

        if (($status['status'] ?? '') !== 'running') {
            return response()->json($status);
        }

        // Check if process is still running
        $pid = $status['pid'] ?? 0;
        $isRunning = $pid > 0 && file_exists("/proc/{$pid}");

        // On macOS, /proc doesn't exist - use kill -0
        if (! $isRunning && $pid > 0) {
            exec("kill -0 {$pid} 2>/dev/null", $output, $exitCode);
            $isRunning = $exitCode === 0;
        }

        // Read last few lines of output for progress info
        $outputContent = '';
        if (file_exists($outputFile)) {
            $outputContent = file_get_contents($outputFile);
        }

        // Calculate elapsed time
        $startedAt = $status['started_at'] ?? now()->toDateTimeString();
        $elapsed = now()->diffInSeconds(\Carbon\Carbon::parse($startedAt));

        if (! $isRunning) {
            // Process finished - check if successful
            $success = str_contains($outputContent, 'Backup completed successfully') ||
                       str_contains($outputContent, 'Uploaded to:');

            $finalStatus = [
                'status' => $success ? 'completed' : 'failed',
                'started_at' => $startedAt,
                'elapsed' => $elapsed,
                'message' => $success ? 'Backup completed successfully!' : 'Backup failed. Check logs for details.',
                'output' => $outputContent,
            ];

            file_put_contents($statusFile, json_encode($finalStatus));

            return response()->json($finalStatus);
        }

        return response()->json([
            'status' => 'running',
            'pid' => $pid,
            'started_at' => $startedAt,
            'elapsed' => $elapsed,
            'message' => $this->parseBackupProgress($outputContent),
        ]);
    }

    protected function parseBackupProgress(string $output): string
    {
        $lines = array_filter(explode("\n", trim($output)));
        if (empty($lines)) {
            return 'Starting backup...';
        }

        $lastLine = trim(end($lines));

        if (str_contains($output, 'Uploading to S3')) {
            return 'Uploading to S3...';
        }
        if (str_contains($output, 'Creating zip')) {
            return 'Compressing backup...';
        }
        if (str_contains($output, 'Dumping database')) {
            return 'Dumping database (throttled)...';
        }
        if (str_contains($output, 'Starting throttled')) {
            return 'Starting throttled backup...';
        }

        return $lastLine ?: 'Processing...';
    }

    public function clean()
    {
        try {
            Artisan::call('backup:clean');
            $output = Artisan::output();

            return back()->with('success', 'Old backups cleaned successfully.')->with('output', $output);
        } catch (\Exception $e) {
            return back()->with('error', 'Cleanup failed: ' . $e->getMessage());
        }
    }

    public function download(string $filename)
    {
        $disk = $this->getBackupDisk();

        if (! $disk) {
            return back()->with('error', 'Backup storage not configured.');
        }

        $path = $this->findBackupFile($disk, $filename);

        if (! $path || ! $disk->exists($path)) {
            return back()->with('error', 'Backup file not found.');
        }

        return response()->streamDownload(function () use ($disk, $path) {
            echo $disk->get($path);
        }, basename($path), [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function delete(string $filename)
    {
        $disk = $this->getBackupDisk();

        if (! $disk) {
            return back()->with('error', 'Backup storage not configured.');
        }

        $path = $this->findBackupFile($disk, $filename);

        if (! $path || ! $disk->exists($path)) {
            return back()->with('error', 'Backup file not found.');
        }

        $disk->delete($path);

        return back()->with('success', 'Backup deleted successfully.');
    }

    public function settings()
    {
        $settings = BackupSetting::pluck('value', 'key')->toArray();

        // Merge .env fallbacks so the user sees what's actually configured
        $envDefaults = [
            's3_key' => config('filesystems.disks.s3.key', ''),
            's3_secret' => config('filesystems.disks.s3.secret', ''),
            's3_region' => config('filesystems.disks.s3.region', 'us-east-1'),
            's3_bucket' => config('filesystems.disks.s3.bucket', ''),
        ];

        foreach ($envDefaults as $key => $envValue) {
            if (empty($settings[$key] ?? '') && ! empty($envValue)) {
                $settings[$key] = $envValue;
            }
        }

        return view('backup.settings', [
            'settings' => $settings,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $fields = [
            's3_key', 's3_secret', 's3_region', 's3_bucket', 's3_path',
            'db_connection',
            'schedule_enabled', 'schedule_frequency', 'schedule_time',
            'cleanup_enabled', 'cleanup_time',
            'retention_keep_all_days', 'retention_daily_days',
            'retention_weekly_weeks', 'retention_monthly_months',
            'retention_max_size_mb',
            'notifications_email',
        ];

        $toggleFields = ['schedule_enabled', 'cleanup_enabled'];

        foreach ($fields as $field) {
            if (in_array($field, $toggleFields)) {
                BackupSetting::set($field, $request->has($field));
            } else {
                $value = $request->input($field, '');
                BackupSetting::set($field, $value);
            }
        }

        return redirect()->route('backup.settings')->with('success', 'Settings saved successfully.');
    }

    public function testConnection(Request $request)
    {
        try {
            $key = $request->input('s3_key') ?: BackupSetting::get('s3_key', '');
            $secret = $request->input('s3_secret') ?: BackupSetting::get('s3_secret', '');
            $region = $request->input('s3_region') ?: BackupSetting::get('s3_region', 'us-east-1');
            $bucket = $request->input('s3_bucket') ?: BackupSetting::get('s3_bucket', '');
            $path = $request->input('s3_path') ?: BackupSetting::get('s3_path', 'backups');

            if (! $key || ! $secret || ! $bucket) {
                return back()->with('error', 'S3 credentials are incomplete. Please fill in Key, Secret, and Bucket.');
            }

            $disk = Storage::build([
                'driver' => 's3',
                'key' => $key,
                'secret' => $secret,
                'region' => $region,
                'bucket' => $bucket,
                'root' => $path,
                'throw' => true,
            ]);

            $testFile = '.backup-connection-test-' . time();
            $disk->put($testFile, 'ok');
            $content = $disk->get($testFile);
            $disk->delete($testFile);

            if ($content === 'ok') {
                return back()->with('success', 'S3 connection successful! Write, read, and delete operations all passed.');
            }

            return back()->with('error', 'S3 connection test failed: read content mismatch.');
        } catch (\Exception $e) {
            return back()->with('error', 'S3 connection failed: ' . $e->getMessage());
        }
    }

    public function logs(Request $request)
    {
        $logFile = storage_path('logs/laravel.log');
        $entries = [];
        $fileSize = 0;
        $lastModified = null;

        if (file_exists($logFile)) {
            $fileSize = filesize($logFile);
            $lastModified = date('Y-m-d H:i:s', filemtime($logFile));

            $lines = $this->tailFile($logFile, 500);
            $entries = $this->parseLogEntries($lines);

            if ($level = $request->query('level')) {
                $entries = array_values(array_filter($entries, function ($entry) use ($level) {
                    return strtolower($entry['level']) === strtolower($level);
                }));
            }
        }

        return view('backup.logs', [
            'entries' => $entries,
            'fileSize' => $this->humanFileSize($fileSize),
            'lastModified' => $lastModified,
            'currentLevel' => $request->query('level', ''),
        ]);
    }

    public function clearLogs()
    {
        $logFile = storage_path('logs/laravel.log');

        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        return back()->with('success', 'Log file cleared successfully.');
    }

    protected function tailFile(string $path, int $lines): string
    {
        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $start = max(0, $totalLines - $lines);
        $file->seek($start);

        $content = '';
        while (! $file->eof()) {
            $content .= $file->fgets();
        }

        return $content;
    }

    protected function parseLogEntries(string $raw): array
    {
        $entries = [];
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[\.\d]*[+\-\d:]*)\]\s+\w+\.(\w+):\s*(.*)/m';

        preg_match_all($pattern, $raw, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $i => $match) {
            $timestamp = $match[1][0];
            $level = strtoupper($match[2][0]);
            $message = trim($match[3][0]);

            $endOfMatch = $match[0][1] + strlen($match[0][0]);
            $nextStart = isset($matches[$i + 1]) ? $matches[$i + 1][0][1] : strlen($raw);
            $stackTrace = trim(substr($raw, $endOfMatch, $nextStart - $endOfMatch));

            $entries[] = [
                'timestamp' => $timestamp,
                'level' => $level,
                'message' => $message,
                'stack_trace' => $stackTrace,
            ];
        }

        return array_reverse($entries);
    }

    protected function getBackupDisk(): ?\Illuminate\Contracts\Filesystem\Filesystem
    {
        try {
            $bucket = config('filesystems.disks.backups.bucket', '');
            if (empty($bucket)) {
                session()->flash('error', 'S3 bucket is not configured. Go to Settings to fill in your S3 credentials.');

                return null;
            }

            return Storage::disk('backups');
        } catch (\Exception) {
            return null;
        }
    }

    protected function findBackupFile($disk, string $filename): ?string
    {
        $files = $disk->allFiles('');

        foreach ($files as $file) {
            if (basename($file) === $filename) {
                return $file;
            }
        }

        return null;
    }

    protected function humanFileSize(int $bytes): string
    {
        if ($bytes === 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
