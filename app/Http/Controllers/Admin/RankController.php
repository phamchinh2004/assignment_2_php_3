<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Http\Requests\StoreRankRequest;
use App\Http\Requests\UpdateRankRequest;
use Illuminate\Support\Facades\Storage;

class RankController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rank = Rank::withCount('orders')->get();
        return view('admin.rank.index', compact('rank'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.rank.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRankRequest $request)
    {
        $data = $request->only(['name', 'commission_percentage', 'upgrade_fee', 'spin_count', 'value', 'maximum_number_of_withdrawals', 'maximum_withdrawal_amount']);
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $file = $request->file('image');
            $file_name = $file->store("uploads/images/ranks", "public");
            $data['image'] = $file_name;
        }
        Rank::create($data);
        return redirect()->route('rank.index')->with('success', 'Tạo cấp độ thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Rank $rank)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Rank $rank)
    {
        return view('admin.rank.edit', compact('rank'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRankRequest $request, Rank $rank)
    {
        $data = $request->only(['name', 'commission_percentage', 'upgrade_fee', 'spin_count', 'value', 'maximum_number_of_withdrawals', 'maximum_withdrawal_amount']);
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            if ($rank->image &&  Storage::exists($rank->image)) {
                Storage::delete($rank->image);
            }
            $file = $request->file('image');
            $file_name = $file->store('uploads/images/ranks', 'public');
            $data['image'] = $file_name;
        }
        $rank->update($data);
        return redirect()->route('rank.index')->with('success', 'Cập nhật cấp độ thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rank $rank)
    {
        //
    }
}
