<?php

namespace Database\Seeders;

use App\Models\BackupSetting;
use Illuminate\Database\Seeder;

class BackupSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            's3_key' => '',
            's3_secret' => '',
            's3_region' => 'us-east-1',
            's3_bucket' => '',
            's3_path' => 'backups',
            'db_connection' => 'mysql',
            'schedule_enabled' => 'false',
            'schedule_frequency' => 'daily',
            'schedule_time' => '01:30',
            'cleanup_enabled' => 'false',
            'cleanup_time' => '01:00',
            'retention_keep_all_days' => '7',
            'retention_daily_days' => '16',
            'retention_weekly_weeks' => '8',
            'retention_monthly_months' => '4',
            'retention_max_size_mb' => '5000',
            'notifications_email' => '',
        ];

        foreach ($defaults as $key => $value) {
            BackupSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
