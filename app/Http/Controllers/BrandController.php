<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Brand::query();

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $brands = $query->get();
            
            return response()->json([
                'success' => true,
                'data' => $brands,
                'total' => $brands->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch brands: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|unique:brands|max:255',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'status' => 'required|in:active,inactive'
            ]);
            
            // Handle image
            $imageUrl = null;
            if($request->hasFile("image")){
                $imageUrl = $request->file("image")->store("brands","public");
            }
            
            $brand = Brand::create([
                "name" => $request->name,
                "code" => $request->code,
                "image" => $imageUrl,
                "status" => $request->status
            ]);

            return response()->json([
                'success' => true,
                "message" =>  "Brand created successfully!",
                'data' => $brand
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create brand: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $brand = Brand::findOrFail($id);
            return response()->json([
                'success' => true,
                "data" =>  $brand
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $brand = Brand::findOrFail($id);
            
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|unique:brands,code,'.$id.'|max:255',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'status' => 'required|in:active,inactive'
            ]);
            
            // Handle image
            if($request->hasFile("image")){
                if($brand->image){
                    Storage::disk("public")->delete($brand->image);
                }
                $brand->image = $request->file("image")->store("brands","public");
            } else if ($request->has('delete_image')) {
                if($brand->image){
                    Storage::disk("public")->delete($brand->image);
                    $brand->image = null;
                }
            }
            
            $brand->update([
                "name" => $request->name,
                "code" => $request->code,
                "status" => $request->status
            ]);
            
            return response()->json([
                'success' => true,
                "message" =>  "Brand updated successfully!",
                'data' => $brand
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update brand: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $brand = Brand::findOrFail($id);
            
            // Check if brand has products
            if($brand->products()->count() > 0){
                return response()->json([
                    'success' => false,
                    "message" => "Cannot delete brand with existing products. Please remove those products first.",
                ], 422);
            }
            
            if($brand->image){
                Storage::disk("public")->delete($brand->image);
            }
            
            $brand->delete();
            
            return response()->json([
                'success' => true,
                "message" =>  "Brand deleted successfully!"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete brand: ' . $e->getMessage()
            ], 500);
        }
    }
}