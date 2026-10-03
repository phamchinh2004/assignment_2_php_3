<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOwnProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    public function update(UpdateOwnProfileRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['full_name', 'email']));
        $user->save();

        $message = 'Cập nhật thông tin tài khoản thành công.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'user' => $user->only(['full_name', 'email']),
            ]);
        }

        return back()->with('success', $message);
    }
}
