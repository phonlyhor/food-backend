<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SpinPrice;

class AdminSpinController extends Controller
{
    // Show all prizes
    public function index()
    {
        return response()->json(SpinPrice::all());
    }

    // Create prize
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prize_type' => 'required|in:coupon,product,point,none',
            'prize_value' => 'nullable|string',
            'chance' => 'required|numeric|min:0|max:100',
            'quantity' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $prize = SpinPrice::create($validated);

        return response()->json([
            'message' => 'Prize created successfully.',
            'data' => $prize,
        ], 201);
    }

    // Show one prize
    public function show($id)
    {
        return response()->json(
            SpinPrice::findOrFail($id)
        );
    }

    // Update prize
    public function update(Request $request, $id)
    {
        $prize = SpinPrice::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prize_type' => 'required|in:coupon,product,point,none',
            'prize_value' => 'nullable|string',
            'chance' => 'required|numeric|min:0|max:100',
            'quantity' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $prize->update($validated);

        return response()->json([
            'message' => 'Prize updated successfully.',
            'data' => $prize,
        ]);
    }

    // Delete prize
    public function destroy($id)
    {
        $prize = SpinPrice::findOrFail($id);

        $prize->delete();

        return response()->json([
            'message' => 'Prize deleted successfully.'
        ]);
    }

    // Show all spin logs history across all users
    public function history()
    {
        return response()->json(
            \App\Models\SpinHistory::with(['user', 'spinPrize'])
                ->latest()
                ->get()
        );
    }
}