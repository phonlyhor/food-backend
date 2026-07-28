<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $banners = Banner::all();
        return response()->json([
            'status' => 200,
            'banners' => $banners
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'link' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);
        $imagePath = $request->file('image')->storeOnCloudinary('banner_images')->getSecurePath();
        $banner = Banner::create([
            'title' => $request->title,
            'image' => $imagePath,
            'link' => $request->link,
            'status' => $request->status,
        ]);
        return response()->json([
            'status' => 201,
            'banner' => $banner
        ], 201);
    }

    /**
     * Display the specified resource.
     */


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
            $banner = Banner::find($id);
            if(!$banner){
                return response()->json([
                    'status' => 404,
                    'message' => 'Banner not found'
                ], 404);
            }
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'link' => 'nullable|string|max:255',
            'status' => 'sometimes|required|in:active,inactive',
        ]);
        $imagePath = $banner->image;
        if($request->hasFile('image')) {
            $imagePath = $request->file('image')->storeOnCloudinary('banner_images')->getSecurePath();
        }
        $banner->update([
            'title' => $request->title ?? $banner->title,
            'image' => $imagePath,
            'link' => $request->link ?? $banner->link,
            'status' => $request->status ?? $banner->status,
        ]);
        return response()->json([
            'status' => 200,
            'banner' => $banner
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $banner = Banner::find($id);
        if(!$banner){
            return response()->json([
                'status' => 404,
                'message' => 'Banner not found'
            ], 404);
        }
        $banner->delete();
        return response()->json([
            'status' => 200,
            'message' => 'Banner deleted successfully'
        ]);
    }
}
