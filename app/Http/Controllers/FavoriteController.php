<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Favorite;
class FavoriteController extends Controller
{
    public function index()
    {
        // Logic to retrieve and return a list of favorite items
        $favorites = auth()->user()->favorites()->with('product')->get();
        return response()->json($favorites);
    }

   public function store(Request $request)
    {
        // Validate Request
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        // Check if product already in favorites
        $favorite = Favorite::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->first();

        if ($favorite) {
            return response()->json([
                'message' => 'Product already in favorites.'
            ], 409);
        }

        // Create Favorite
        $favorite = Favorite::create([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id,
        ]);

        return response()->json([
            'message' => 'Favorite added successfully.',
            'data' => $favorite
        ], 201);
    }

    public function destroy($id)
    {
        $favorite = Favorite::where('user_id', auth()->id())
            ->where('id', $id)
            ->first();

        if (!$favorite) {
            return response()->json([
                'message' => 'Favorite not found.'
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'message' => 'Favorite removed successfully.'
        ]);
    }
}
