<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction_history;
use App\Models\Wallet_balance_history;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BalanceFluctuationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tab = $_GET['tab'];
        $list_distribution = null;
        $list_deposit = null;
        $list_withdraw = null;
        if ($tab) {
            if ($tab === "distribution") {
                $list_distribution = Transaction_history::where('user_id', Auth::user()->id)->orderByDesc('id')->get();
            } else if ($tab === "deposit") {
                $list_deposit = Wallet_balance_history::where('user_id', Auth::user()->id)->where('type', 'deposit')->get();
            } else if ($tab === "withdraw") {
                $list_withdraw = Wallet_balance_history::where('user_id', Auth::user()->id)->where('type', 'withdraw')->get();
            }
        } else {
            $list_distribution = Transaction_history::where('user_id', Auth::user()->id)->get();
        }
        // dd($list_deposit);
        return view('user.balance_fluctuation', compact('list_distribution', 'list_deposit', 'list_withdraw'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
