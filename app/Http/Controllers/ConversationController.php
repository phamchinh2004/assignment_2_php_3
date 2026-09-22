<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!in_array(Auth::user()->role, User::MANAGEMENT_ROLES, true)) {
            abort(403, 'Unauthorized');
        }

        return view('admin.chat.index');
    }

}
