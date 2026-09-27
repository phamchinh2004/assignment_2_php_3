<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReactPageService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ReactPageService $reactPage)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return $this->reactPage->admin('admin.dashboard', [], 'Dashboard');
    }
}
