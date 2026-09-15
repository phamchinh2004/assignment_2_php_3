<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!in_array(Auth::user()->role, ['admin', 'staff'])) {
            abort(403, 'Unauthorized');
        }

        return view('admin.chat.index');
    }

}
