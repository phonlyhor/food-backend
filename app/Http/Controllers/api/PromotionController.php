<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Promotion::with('products')->get()
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $promotion = Promotion::with('products')->find($id);

        if (! $promotion) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $promotion
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
            'type' => 'required|string',
            'discount_value' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|boolean',

            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
        ]);

        DB::beginTransaction();

        try {

            $promotion = Promotion::create([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'discount_value' => $request->discount_value,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => $request->status,
            ]);

            // Attach selected products
            $promotion->products()->attach($request->product_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Promotion created successfully.',
                'data' => $promotion->load('products')
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $promotion = Promotion::find($id);

        if (! $promotion) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion not found'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|string',
            'discount_value' => 'sometimes|required|numeric',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'status' => 'sometimes|required|boolean',

            'product_ids' => 'sometimes|required|array|min:1',
            'product_ids.*' => 'exists:products,id',
        ]);

        DB::beginTransaction();

        try {
            $promotion->update([
                'name' => $request->name ?? $promotion->name,
                'description' => $request->description ?? $promotion->description,
                'type' => $request->type ?? $promotion->type,
                'discount_value' => $request->discount_value ?? $promotion->discount_value,
                'start_date' => $request->start_date ?? $promotion->start_date,
                'end_date' => $request->end_date ?? $promotion->end_date,
                'status' => $request->has('status') ? $request->status : $promotion->status,
            ]);

            // sync() replaces the pivot rows entirely instead of piling on
            // duplicates the way attach() would on every edit.
            if ($request->has('product_ids')) {
                $promotion->products()->sync($request->product_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Promotion updated successfully.',
                'data' => $promotion->load('products')
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $promotion = Promotion::find($id);

        if (! $promotion) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion not found'
            ], 404);
        }

        DB::beginTransaction();

        try {
            // detach pivot rows first so the products table itself is untouched
            $promotion->products()->detach();
            $promotion->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Promotion deleted successfully.'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}