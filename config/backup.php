<?php

use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;

$projectBackupPath = rtrim((string) (env('PROJECT_BACKUP_PATH') ?: base_path()), '/\\');

return [
    'backup' => [
        'name' => env('APP_NAME', 'laravel-backup'),

        'source' => [
            'files' => [
                'include' => [
                    $projectBackupPath,
                ],
                'exclude' => [
                    $projectBackupPath.'/.git',
                    $projectBackupPath.'/storage/app/backup-temp',
                    $projectBackupPath.'/storage/app/'.env('APP_NAME', 'laravel-backup'),
                    $projectBackupPath.'/storage/framework/cache',
                    $projectBackupPath.'/storage/framework/sessions',
                    $projectBackupPath.'/storage/framework/views',
                    $projectBackupPath.'/storage/logs',
                ],
                'follow_links' => false,
                'ignore_unreadable_directories' => true,
                'relative_path' => $projectBackupPath,
            ],
            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 9,
            'filename_prefix' => '',
            'disks' => [
                env('BACKUP_DISK', 'r2'),
            ],
        ],
    ],

    'schedule' => [
        'project_backup_time' => env('PROJECT_BACKUP_TIME', env('FULL_BACKUP_TIME', '04:00')),
        'timezone' => env('BACKUP_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    ],

    'snapshots' => [
        'db' => [
            'filename_prefix' => 'db-',
            'max_copies' => (int) env('BACKUP_DB_MAX_COPIES', 2),
            'max_age_minutes' => (int) env('BACKUP_DB_MAX_AGE_MINUTES', 60),
        ],
        'project' => [
            'filename_prefix' => 'project-',
            'max_copies' => (int) env('BACKUP_PROJECT_MAX_COPIES', 2),
            'max_age_minutes' => (int) env('BACKUP_PROJECT_MAX_AGE_HOURS', 48) * 60,
        ],
    ],

    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'laravel-backup'),
            'disks' => [env('BACKUP_DISK', 'r2')],
            'health_checks' => [
                MaximumAgeInDays::class => 2,
                MaximumStorageInMegabytes::class => (int) env('BACKUP_MAX_STORAGE_MB', 1024),
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => (int) env('BACKUP_KEEP_ALL_DAYS', 7),
            'keep_daily_backups_for_days' => (int) env('BACKUP_KEEP_DAILY_DAYS', 14),
            'keep_weekly_backups_for_weeks' => (int) env('BACKUP_KEEP_WEEKLY_WEEKS', 4),
            'keep_monthly_backups_for_months' => (int) env('BACKUP_KEEP_MONTHLY_MONTHS', 3),
            'keep_yearly_backups_for_years' => (int) env('BACKUP_KEEP_YEARLY_YEARS', 0),
            'delete_oldest_backups_when_using_more_megabytes_than' => (int) env('BACKUP_MAX_STORAGE_MB', 1024),
        ],

        'tries' => 1,
        'retry_delay' => 0,
    ],
];
