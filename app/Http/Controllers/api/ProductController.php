<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Product_Varaints; // Keep this if your model file is named this way
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
   public function index()
{
    $products = Product::with([
        'category',
        'productVariants',
        'promotions' => function ($query) {
            $query->where('status', 1)
                  ->whereDate('start_date', '<=', now())
                  ->whereDate('end_date', '>=', now());
        }
    ])->get();

    return response()->json($products);
}

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'status'      => 'required|in:active,inactive',
            'variants'    => 'required|array|min:1',
            'variants.*.size'  => 'required|string|max:255',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $imagePath = $request->hasFile('image') 
                ? $request->file('image')->storeOnCloudinary('product_images')->getSecurePath() 
                : null;

            $product = Product::create([
                'category_id' => $request->category_id,
                'name'        => $request->name,
                'description' => $request->description ?? '',
                'image'       => $imagePath ?? '',
                'status'      => $request->status,
            ]);

            foreach ($request->input('variants') as $variant) {
                $variant['status'] = 'active';
                $product->productVariants()->create($variant);
            }

            DB::commit();

$product->load([
    'category',
    'productVariants',
    'promotions' => function ($query) {
        $query->where('status', 1)
              ->whereDate('start_date', '<=', now())
              ->whereDate('end_date', '>=', now());
    }
]);

return response()->json([
    'message' => 'Product created successfully',
    'data' => $product
], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create product',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function show(string $id)
    {
        $product = Product::with([
            'category',
            'productVariants',
            'promotions' => function ($query) {
                $query->where('status', 1)
                      ->whereDate('start_date', '<=', now())
                      ->whereDate('end_date', '>=', now());
            }
        ])->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }
    public function update(Request $request, string $id)
    {
        $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'status'      => 'sometimes|required|in:active,inactive',
            'variants'    => 'sometimes|array|min:1',
            'variants.*.id'    => 'nullable|integer|exists:product_varaints,id', 
            'variants.*.size'  => 'required|string|max:255',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
        ]);

        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        DB::beginTransaction();

        try {
            // Handle Image
            if ($request->hasFile('image')) {
                $product->image = $request->file('image')->storeOnCloudinary('product_images')->getSecurePath();
            }

            $fillData = $request->only(['category_id', 'name', 'description', 'status']);
            if (array_key_exists('description', $fillData)) {
                $fillData['description'] = $fillData['description'] ?? '';
            }
            $product->fill($fillData);
            $product->save();

            // Handle Variants
            if ($request->has('variants')) {
                $incomingIds = [];

                foreach ($request->input('variants') as $variantData) {
                    if (isset($variantData['id']) && $variantData['id']) {
                        $variant = Product_Varaints::where('product_id', $product->id)
                                    ->find($variantData['id']);

                        if ($variant) {
                            $variant->update([
                                'size'  => $variantData['size'],
                                'price' => $variantData['price'],
                                'stock' => $variantData['stock'],
                            ]);
                            $incomingIds[] = $variant->id;
                        }
                    } else {
                        // FIXED: changed productVaraints() to productVariants()
                        $newVariant = $product->productVariants()->create([
                            'size'  => $variantData['size'],
                            'price' => $variantData['price'],
                            'stock' => $variantData['stock'],
                            'status' => 'active',
                        ]);
                        $incomingIds[] = $newVariant->id;
                    }
                }

                // Delete removed variants
                // FIXED: changed productVaraints() to productVariants()
                $product->productVariants()
                    ->whereNotIn('id', $incomingIds)
                    ->delete();
            }

            DB::commit();

        $product->load([
    'category',
    'productVariants',
    'promotions' => function ($query) {
        $query->where('status', 1)
              ->whereDate('start_date', '<=', now())
              ->whereDate('end_date', '>=', now());
    }
]);

return response()->json([
    'message' => 'Product updated successfully',
    'data' => $product
]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update product',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        DB::beginTransaction();

        try {
            $product->productVariants()->delete();



            $product->delete();

            DB::commit();
            return response()->json(['message' => 'Product deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete product',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}