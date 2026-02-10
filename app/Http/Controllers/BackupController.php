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
        try {
            Artisan::call('backup:run', ['--only-db' => true]);
            $output = Artisan::output();

            return back()->with('success', 'Database backup completed successfully.')->with('output', $output);
        } catch (\Exception $e) {
            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
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

    protected function getBackupDisk(): ?\Illuminate\Contracts\Filesystem\Filesystem
    {
        try {
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
