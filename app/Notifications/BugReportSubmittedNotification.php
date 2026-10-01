<?php

namespace App\Notifications;

use App\Models\BugReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BugReportSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly BugReport $bugReport)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $reporter = $this->bugReport->reporter;
        $reporterName = $reporter?->full_name ?: $reporter?->username ?: 'Nhân viên';

        return [
            'type' => 'bug_report',
            'title' => 'Có báo lỗi mới',
            'message' => $reporterName.': '.$this->bugReport->title,
            'bug_report_id' => $this->bugReport->id,
            'target_url' => route('bug_reports.show', $this->bugReport),
        ];
    }
}
