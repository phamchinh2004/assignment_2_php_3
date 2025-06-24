<?php

namespace App\Http\Controllers\Admin;

use App\Models\BannerImage;
use App\Http\Requests\StoreBannerImageRequest;
use App\Http\Requests\UpdateBannerImageRequest;
use App\Http\Controllers\Controller;
class BannerImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(StoreBannerImageRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(BannerImage $bannerImage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BannerImage $bannerImage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBannerImageRequest $request, BannerImage $bannerImage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BannerImage $bannerImage)
    {
        //
    }
}
