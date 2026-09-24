<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeatureAnnouncementRequest;
use App\Models\FeatureAnnouncement;
use App\Models\User;
use App\Services\FeatureAnnouncementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class FeatureAnnouncementController extends Controller
{
    public function index(FeatureAnnouncementService $service): View
    {
        $announcements = FeatureAnnouncement::query()
            ->with('creator:id,full_name,username')
            ->latest()
            ->paginate(20);

        $announcementStats = $service->getStatsForAnnouncements($announcements->getCollection());

        return view('admin.feature_announcements.index', compact('announcements', 'announcementStats'));
    }

    public function create(): View
    {
        return view('admin.feature_announcements.create', [
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function store(FeatureAnnouncementRequest $request): RedirectResponse
    {
        $imagePath = null;

        try {
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('uploads/images/feature-announcements', 'public');
            }

            $data = $this->payload($request);
            $data['image_path'] = $imagePath;
            $data['created_by'] = Auth::id();
            $data['version'] = 1;

            FeatureAnnouncement::create($data);

            return redirect()
                ->route('feature_announcements.index')
                ->with('success', 'Tạo thông báo tính năng thành công.');
        } catch (Throwable $e) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            report($e);

            return back()->withInput()->with('error', 'Không thể tạo thông báo. Vui lòng thử lại.');
        }
    }

    public function show(
        FeatureAnnouncement $featureAnnouncement,
        FeatureAnnouncementService $service
    ): View {
        $featureAnnouncement->load('creator:id,full_name,username');
        $stats = $service->getAnnouncementStats($featureAnnouncement);

        return view('admin.feature_announcements.show', compact('featureAnnouncement', 'stats'));
    }

    public function edit(FeatureAnnouncement $featureAnnouncement): View
    {
        return view('admin.feature_announcements.edit', [
            'featureAnnouncement' => $featureAnnouncement,
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function update(
        FeatureAnnouncementRequest $request,
        FeatureAnnouncement $featureAnnouncement
    ): RedirectResponse {
        $oldImagePath = $featureAnnouncement->image_path;
        $newImagePath = null;

        try {
            $data = $this->payload($request);

            if ($request->boolean('require_reacknowledgement')) {
                $data['version'] = $featureAnnouncement->version + 1;
            }

            if ($request->hasFile('image')) {
                $newImagePath = $request->file('image')->store('uploads/images/feature-announcements', 'public');
                $data['image_path'] = $newImagePath;
            } elseif ($request->boolean('remove_image')) {
                $data['image_path'] = null;
            }

            $featureAnnouncement->update($data);

            if (
                $oldImagePath
                && $oldImagePath !== $featureAnnouncement->image_path
                && Storage::disk('public')->exists($oldImagePath)
            ) {
                Storage::disk('public')->delete($oldImagePath);
            }

            return redirect()
                ->route('feature_announcements.index')
                ->with('success', 'Cập nhật thông báo tính năng thành công.');
        } catch (Throwable $e) {
            if ($newImagePath && Storage::disk('public')->exists($newImagePath)) {
                Storage::disk('public')->delete($newImagePath);
            }

            report($e);

            return back()->withInput()->with('error', 'Không thể cập nhật thông báo. Vui lòng thử lại.');
        }
    }

    public function toggle(FeatureAnnouncement $featureAnnouncement): RedirectResponse
    {
        $featureAnnouncement->update([
            'is_active' => !$featureAnnouncement->is_active,
        ]);

        return back()->with(
            'success',
            $featureAnnouncement->is_active
                ? 'Đã bật thông báo tính năng.'
                : 'Đã tắt thông báo tính năng.'
        );
    }

    public function destroy(FeatureAnnouncement $featureAnnouncement): RedirectResponse
    {
        $imagePath = $featureAnnouncement->image_path;
        $featureAnnouncement->delete();

        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }

        return redirect()
            ->route('feature_announcements.index')
            ->with('success', 'Đã xóa thông báo tính năng.');
    }

    public function acknowledge(
        FeatureAnnouncement $featureAnnouncement,
        FeatureAnnouncementService $service
    ): JsonResponse {
        $read = $service->acknowledge(request()->user(), $featureAnnouncement);

        return response()->json([
            'message' => 'Đã ghi nhận xác nhận.',
            'acknowledged_at' => $read->acknowledged_at?->toIso8601String(),
        ]);
    }

    private function payload(FeatureAnnouncementRequest $request): array
    {
        $validated = $request->validated();

        return [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'priority' => $validated['priority'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'target_roles' => array_values(array_unique($validated['target_roles'])),
            'action_text' => $validated['action_text'] ?? null,
            'action_url' => $validated['action_url'] ?? null,
        ];
    }

    private function roleOptions(): array
    {
        return [
            User::ROLE_OWNER => 'Chủ hệ thống',
            User::ROLE_ADMIN => 'Quản trị viên',
            User::ROLE_STAFF => 'Nhân viên',
        ];
    }
}
