<?php

namespace App\Services;

use App\Models\BackupSetting;
use Illuminate\Support\Facades\Schema;

class BackupConfigService
{
    public function applyConfig(): void
    {
        if (! Schema::hasTable('backup_settings')) {
            return;
        }

        $settings = BackupSetting::pluck('value', 'key')->toArray();

        $this->configureS3Disk($settings);
        $this->configureBackup($settings);
        $this->configureCleanup($settings);
        $this->configureNotifications($settings);
    }

    protected function configureS3Disk(array $settings): void
    {
        $key = $settings['s3_key'] ?? '' ?: config('filesystems.disks.s3.key', '');
        $secret = $settings['s3_secret'] ?? '' ?: config('filesystems.disks.s3.secret', '');
        $region = $settings['s3_region'] ?? '' ?: config('filesystems.disks.s3.region', 'us-east-1');
        $bucket = $settings['s3_bucket'] ?? '' ?: config('filesystems.disks.s3.bucket', '');
        $path = $settings['s3_path'] ?? '' ?: 'backups';

        config(['filesystems.disks.backups' => [
            'driver' => 's3',
            'key' => $key,
            'secret' => $secret,
            'region' => $region,
            'bucket' => $bucket,
            'root' => $path,
            'throw' => true,
        ]]);
    }

    protected function configureBackup(array $settings): void
    {
        $dbConnection = $settings['db_connection'] ?? 'mysql';

        config([
            'backup.backup.source.files.include' => [],
            'backup.backup.source.databases' => [$dbConnection],
            'backup.backup.destination.disks' => ['backups'],
        ]);

        config([
            'backup.monitor_backups' => [
                [
                    'name' => config('app.name', 'laravel-backup'),
                    'disks' => ['backups'],
                    'health_checks' => [
                        \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                        \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => (int) ($settings['retention_max_size_mb'] ?? 5000),
                    ],
                ],
            ],
        ]);
    }

    protected function configureCleanup(array $settings): void
    {
        config([
            'backup.cleanup.default_strategy.keep_all_backups_for_days' => (int) ($settings['retention_keep_all_days'] ?? 7),
            'backup.cleanup.default_strategy.keep_daily_backups_for_days' => (int) ($settings['retention_daily_days'] ?? 16),
            'backup.cleanup.default_strategy.keep_weekly_backups_for_weeks' => (int) ($settings['retention_weekly_weeks'] ?? 8),
            'backup.cleanup.default_strategy.keep_monthly_backups_for_months' => (int) ($settings['retention_monthly_months'] ?? 4),
            'backup.cleanup.default_strategy.delete_oldest_backups_when_using_more_megabytes_than' => (int) ($settings['retention_max_size_mb'] ?? 5000),
        ]);
    }

    protected function configureNotifications(array $settings): void
    {
        $email = $settings['notifications_email'] ?? '';

        if ($email) {
            config(['backup.notifications.mail.to' => $email]);
        }
    }

    public function getScheduleConfig(): array
    {
        if (! Schema::hasTable('backup_settings')) {
            return ['enabled' => false];
        }

        return [
            'enabled' => BackupSetting::get('schedule_enabled', false),
            'frequency' => BackupSetting::get('schedule_frequency', 'daily'),
            'time' => BackupSetting::get('schedule_time', '01:30'),
            'cleanup_enabled' => BackupSetting::get('cleanup_enabled', false),
            'cleanup_time' => BackupSetting::get('cleanup_time', '01:00'),
        ];
    }
}
