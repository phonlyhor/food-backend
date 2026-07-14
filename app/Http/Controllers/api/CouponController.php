<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupons;
use Illuminate\Support\Facades\Storage;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $coupons = Coupons::all();
        return response()->json([
            'success' => true,
            'data' => $coupons
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'required|string|max:50|unique:coupons,code',
            'type'           => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'minimum_order'  => 'required|numeric|min:0',
            'usage_limit'    => 'required|integer|min:1',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'status'         => 'required|boolean',
        ]);

        try {
            $coupon = Coupons::create([
                'name'           => $request->name,
                'code'           => strtoupper($request->code),
                'type'           => $request->type,
                'discount_value' => $request->discount_value,
                'minimum_order'  => $request->minimum_order,
                'usage_limit'    => $request->usage_limit,
                'used_count'     => 0,
                'start_date'     => $request->start_date,
                'end_date'       => $request->end_date,
                'status'         => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Coupon created successfully.',
                'data'    => $coupon
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create coupon',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $coupon = Coupons::find($id);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $coupon
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $coupon = Coupons::find($id);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found'
            ], 404);
        }

        $request->validate([
            'name'           => 'sometimes|required|string|max:255',
            'code'           => 'sometimes|required|string|max:50|unique:coupons,code,' . $id,
            'type'           => 'sometimes|required|in:percentage,fixed',
            'discount_value' => 'sometimes|required|numeric|min:0',
            'minimum_order'  => 'sometimes|required|numeric|min:0',
            'usage_limit'    => 'sometimes|required|integer|min:1',
            'start_date'     => 'sometimes|required|date',
            'end_date'       => 'sometimes|required|date|after_or_equal:start_date',
            'status'         => 'sometimes|required|boolean',
        ]);

        try {
            $coupon->update([
                'name'           => $request->name ?? $coupon->name,
                'code'           => $request->code ? strtoupper($request->code) : $coupon->code,
                'type'           => $request->type ?? $coupon->type,
                'discount_value' => $request->discount_value ?? $coupon->discount_value,
                'minimum_order'  => $request->minimum_order ?? $coupon->minimum_order,
                'usage_limit'    => $request->usage_limit ?? $coupon->usage_limit,
                'start_date'     => $request->start_date ?? $coupon->start_date,
                'end_date'       => $request->end_date ?? $coupon->end_date,
                'status'         => $request->status ?? $coupon->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Coupon updated successfully.',
                'data'    => $coupon
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update coupon',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $coupon = Coupons::find($id);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found'
            ], 404);
        }

        try {
            $coupon->delete();

            return response()->json([
                'success' => true,
                'message' => 'Coupon deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete coupon',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}