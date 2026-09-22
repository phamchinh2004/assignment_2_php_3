<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminHeaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeaderStateController extends Controller
{
    public function show(Request $request, AdminHeaderService $header): JsonResponse
    {
        $user = $this->adminUser($request);
        $notificationLimit = (int) $request->integer('notification_limit', 6);

        return response()->json($header->state($user, $notificationLimit));
    }

    public function markNotificationRead(
        Request $request,
        string $notification,
        AdminHeaderService $header
    ): JsonResponse {
        $user = $this->adminUser($request);
        $databaseNotification = $user->notifications()->whereKey($notification)->firstOrFail();

        if ($databaseNotification->read_at === null) {
            $databaseNotification->markAsRead();
        }

        return response()->json($header->state($user));
    }

    public function markAllNotificationsRead(Request $request, AdminHeaderService $header): JsonResponse
    {
        $user = $this->adminUser($request);
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json($header->state($user));
    }

    private function adminUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, User::MANAGEMENT_ROLES, true), 403);

        return $user;
    }
}
