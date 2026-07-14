<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
           
        $categories = Category::all();
        return response()->json([
            'status' => 200,
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
             'status' => 'required|in:active,inactive',
        ]);
        $imagePath = null;
        if($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
        }
        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $imagePath,
            'status' => $request->status
        ]);
        return response()->json([
            'status' => 201,
            'category' => $category
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::find($id);
        if($category) {
            return response()->json([
                'status' => 200,
                'category' => $category
            ]);
        } else {
                return response()->json([
                    'status' => 404,
                    'message' => 'Category not found'
                ]);
            }
        }
    

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::find($id);
        if(!$category) {
            return response()->json([
                'status' => 404,
                'message' => 'Category not found'
            ]);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'status' => 'required|in:active,inactive',
        ]);
        $imagePath = $category->image;
        if($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
        }
        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $imagePath,
            'status' => $request->status
        ]);
        return response()->json([
            'status' => 200,
            'category' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::find($id);
        if(!$category) {
            return response()->json([
                'status' => 404,
                'message' => 'Category not found'
            ]);
        }
        $category->delete();
        return response()->json([
            'status' => 200,
            'message' => 'Category deleted successfully'
        ]);
    }
}
