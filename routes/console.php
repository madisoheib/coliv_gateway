<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\BackupSetting;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dynamic backup scheduling from DB settings
try {
    if (BackupSetting::get('schedule_enabled', false)) {
        $frequency = BackupSetting::get('schedule_frequency', 'daily');
        $time = BackupSetting::get('schedule_time', '01:30');

        $backup = Schedule::command('backup:run --only-db');

        match ($frequency) {
            'hourly' => $backup->hourly(),
            'weekly' => $backup->weeklyOn(1, $time),
            default => $backup->dailyAt($time),
        };
    }

    if (BackupSetting::get('cleanup_enabled', false)) {
        $cleanupTime = BackupSetting::get('cleanup_time', '01:00');
        Schedule::command('backup:clean')->dailyAt($cleanupTime);
    }
} catch (\Exception) {
    // Table may not exist yet (before migration)
}
