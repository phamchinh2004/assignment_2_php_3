<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveBugReportRequest;
use App\Http\Requests\StoreBugReportRequest;
use App\Models\BugReport;
use App\Models\User;
use App\Notifications\BugReportSubmittedNotification;
use App\Services\ReactPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BugReportController extends Controller
{
    public function __construct(private readonly ReactPageService $reactPage)
    {
    }

    public function index(Request $request): View|JsonResponse
    {
        $status = (string) $request->input('status', BugReport::STATUS_PENDING);
        if (! in_array($status, [BugReport::STATUS_PENDING, BugReport::STATUS_RESOLVED, 'all'], true)) {
            $status = BugReport::STATUS_PENDING;
        }

        $reports = BugReport::query()
            ->with(['reporter:id,username,full_name,role', 'resolver:id,username,full_name'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return $this->reactPage->admin('admin.bug-reports.index', [
            'reports' => $reports,
            'status' => $status,
            'routes' => [
                'index' => route('bug_reports.index'),
                'show' => route('bug_reports.show', ['bugReport' => '__REPORT_ID__']),
            ],
        ], 'Báo lỗi hệ thống');
    }

    public function store(StoreBugReportRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $storedPaths = [];

        try {
            $report = DB::transaction(function () use ($request, $user, $data, &$storedPaths) {
                $report = BugReport::query()->create([
                    'reported_by' => $user->id,
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'page_url' => $data['page_url'] ?? null,
                    'user_agent' => $data['user_agent'] ?? null,
                    'status' => BugReport::STATUS_PENDING,
                ]);

                foreach ($request->file('images', []) as $image) {
                    $storedPaths[] = $image->store('bug-reports/'.$report->id, 'public');
                }

                if ($storedPaths !== []) {
                    $report->update(['images' => $storedPaths]);
                }

                return $report;
            });
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            throw $exception;
        }

        $report->load('reporter:id,username,full_name');

        User::query()
            ->where('role', User::ROLE_OWNER)
            ->get()
            ->each(fn (User $owner) => $owner->notify(new BugReportSubmittedNotification($report)));

        return response()->json([
            'message' => 'Đã gửi báo lỗi tới chủ hệ thống.',
            'report_id' => $report->id,
        ], 201);
    }

    public function show(BugReport $bugReport): View|JsonResponse
    {
        $bugReport->load(['reporter:id,username,full_name,role', 'resolver:id,username,full_name']);
        $bugReport->setAttribute('image_urls', collect($bugReport->images ?? [])
            ->map(fn (string $path) => Storage::disk('public')->url($path))
            ->values()
            ->all());

        return $this->reactPage->admin('admin.bug-reports.show', [
            'report' => $bugReport,
            'routes' => [
                'index' => route('bug_reports.index'),
                'resolve' => route('bug_reports.resolve', $bugReport),
            ],
        ], 'Chi tiết báo lỗi');
    }

    public function resolve(ResolveBugReportRequest $request, BugReport $bugReport)
    {
        $resolved = DB::transaction(function () use ($request, $bugReport) {
            $report = BugReport::query()->lockForUpdate()->findOrFail($bugReport->id);

            if ($report->status === BugReport::STATUS_RESOLVED) {
                return false;
            }

            $report->update([
                'status' => BugReport::STATUS_RESOLVED,
                'resolved_by' => $request->user()->id,
                'resolved_note' => $request->validated('resolved_note'),
                'resolved_at' => now(),
            ]);

            return true;
        });

        return redirect()
            ->route('bug_reports.show', $bugReport)
            ->with($resolved ? 'success' : 'warning', $resolved ? 'Đã đánh dấu báo lỗi là đã xử lý.' : 'Báo lỗi này đã được xử lý trước đó.');
    }
}
