<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ReactPageService;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ReactPageService $reactPageService)
    {
        if (!in_array(Auth::user()->role, User::MANAGEMENT_ROLES, true)) {
            abort(403, 'Unauthorized');
        }

        return $reactPageService->admin('admin.chat', [
            'html' => app('livewire')->mount('admin.chat-component'),
        ], 'Chat System');
    }

}
