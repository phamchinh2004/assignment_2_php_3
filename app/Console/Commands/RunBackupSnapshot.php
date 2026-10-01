<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class RunBackupSnapshot extends Command
{
    protected $signature = 'backup:snapshot {type : db or project}';

    protected $description = 'Create and rotate a database or project backup snapshot';

    public function handle(): int
    {
        $type = (string) $this->argument('type');
        $settings = config("backup.snapshots.{$type}");

        if (! is_array($settings) || ! in_array($type, ['db', 'project'], true)) {
            $this->error('Backup type must be db or project.');

            return self::FAILURE;
        }

        $timezone = (string) config('backup.schedule.timezone', 'Asia/Ho_Chi_Minh');
        $now = CarbonImmutable::now($timezone);
        $prefix = (string) $settings['filename_prefix'];
        $filename = $prefix.$now->format('Y-m-d-H-i-s').'.zip';

        $options = [
            '--filename' => $filename,
            '--disable-notifications' => true,
        ];
        $options[$type === 'db' ? '--only-db' : '--only-files'] = true;

        $exitCode = Artisan::call('backup:run', $options, $this->output);

        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        $deleted = $this->pruneType($type, $settings, $now);
        $legacyDeleted = $this->pruneLegacyBackups();

        $this->info("Backup snapshot {$filename} completed; removed {$deleted} old {$type} backup(s).".($legacyDeleted > 0 ? " Removed {$legacyDeleted} legacy backup(s)." : ''));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function pruneType(string $type, array $settings, CarbonImmutable $now): int
    {
        $disk = $this->backupDisk();
        $backupName = $this->backupName();
        $prefix = (string) $settings['filename_prefix'];
        $maxCopies = max(1, (int) ($settings['max_copies'] ?? 2));
        $maxAgeMinutes = max(1, (int) ($settings['max_age_minutes'] ?? 60));
        $cutoff = $now->subMinutes($maxAgeMinutes);

        $files = collect($disk->files($backupName))
            ->filter(fn (string $path) => $this->isSnapshotFile($path, $prefix))
            ->sortDesc()
            ->values();

        $toDelete = $files->filter(function (string $path, int $index) use ($prefix, $maxCopies, $cutoff): bool {
            if ($index >= $maxCopies) {
                return true;
            }

            // Always preserve the newest successful snapshot, even after a long outage.
            if ($index === 0) {
                return false;
            }

            $createdAt = $this->snapshotTimestamp($path, $prefix);

            return $createdAt?->lessThan($cutoff) ?? false;
        })->values();

        if ($toDelete->isEmpty()) {
            return 0;
        }

        if (! $disk->delete($toDelete->all())) {
            throw new \RuntimeException("Could not delete old {$type} backups from {$backupName}.");
        }

        return $toDelete->count();
    }

    private function pruneLegacyBackups(): int
    {
        $disk = $this->backupDisk();
        $backupName = $this->backupName();
        $files = collect($disk->files($backupName));

        $dbPrefix = (string) config('backup.snapshots.db.filename_prefix', 'db-');
        $projectPrefix = (string) config('backup.snapshots.project.filename_prefix', 'project-');

        $dbCount = $files->filter(fn (string $path) => $this->isSnapshotFile($path, $dbPrefix))->count();
        $projectCount = $files->filter(fn (string $path) => $this->isSnapshotFile($path, $projectPrefix))->count();

        // Keep old untyped Spatie backups until two snapshots of both new types exist.
        if ($dbCount < 2 || $projectCount < 2) {
            return 0;
        }

        $legacy = $files
            ->filter(fn (string $path) => preg_match('/^\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}\.zip$/', basename($path)) === 1)
            ->values();

        if ($legacy->isEmpty()) {
            return 0;
        }

        if (! $disk->delete($legacy->all())) {
            throw new \RuntimeException("Could not delete legacy backups from {$backupName}.");
        }

        return $legacy->count();
    }

    private function isSnapshotFile(string $path, string $prefix): bool
    {
        return preg_match(
            '/^'.preg_quote($prefix, '/').'\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}\.zip$/',
            basename($path)
        ) === 1;
    }

    private function snapshotTimestamp(string $path, string $prefix): ?CarbonImmutable
    {
        $basename = basename($path);
        $timestamp = substr($basename, strlen($prefix), 19);

        try {
            return CarbonImmutable::createFromFormat(
                'Y-m-d-H-i-s',
                $timestamp,
                (string) config('backup.schedule.timezone', 'Asia/Ho_Chi_Minh')
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function backupDisk(): \Illuminate\Filesystem\FilesystemAdapter
    {
        $diskName = (string) config('backup.backup.destination.disks.0', 'r2');

        return Storage::disk($diskName);
    }

    private function backupName(): string
    {
        return (string) config('backup.backup.name', config('app.name', 'laravel-backup'));
    }
}
