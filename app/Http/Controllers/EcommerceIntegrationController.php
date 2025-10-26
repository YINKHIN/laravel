<?php

namespace App\Http\Controllers;

use App\Services\EcommerceIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class EcommerceIntegrationController extends Controller
{
    protected $ecommerceService;
    
    
    public function __construct(EcommerceIntegrationService $ecommerceService)
    {
        $this->ecommerceService = $ecommerceService;
    }
    
    /**
     * Sync product to Shopify
     */
    public function syncProductToShopify(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string',
                'body_html' => 'nullable|string',
                'vendor' => 'nullable|string',
                'product_type' => 'nullable|string',
                'variants' => 'required|array',
                'variants.*.option1' => 'nullable|string',
                'variants.*.price' => 'required|numeric',
                'variants.*.sku' => 'nullable|string',
                'variants.*.inventory_quantity' => 'required|integer',
                'images' => 'nullable|array',
                'images.*.src' => 'nullable|url',
            ]);
            
            $productData = [
                'title' => $validatedData['title'],
                'body_html' => $validatedData['body_html'] ?? '',
                'vendor' => $validatedData['vendor'] ?? 'Inventory System',
                'product_type' => $validatedData['product_type'] ?? 'Default',
                'variants' => array_map(function($variant) {
                    return [
                        'option1' => $variant['option1'] ?? 'Default',
                        'price' => $variant['price'],
                        'sku' => $variant['sku'] ?? '',
                        'inventory_quantity' => $variant['inventory_quantity'],
                        'inventory_management' => 'shopify'
                    ];
                }, $validatedData['variants']),
                'images' => array_map(function($image) {
                    return [
                        'src' => $image['src']
                    ];
                }, $validatedData['images'] ?? [])
            ];
            
            $result = $this->ecommerceService->syncProductToShopify($productData);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Product synced to Shopify successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Shopify product sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync product to Shopify: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update Shopify inventory
     */
    public function updateShopifyInventory(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'product_id' => 'required|string',
                'variants' => 'required|array',
                'variants.*.id' => 'required|string',
                'variants.*.inventory_quantity' => 'required|integer',
            ]);
            
            $inventoryData = [
                'variants' => array_map(function($variant) {
                    return [
                        'id' => $variant['id'],
                        'inventory_quantity' => $variant['inventory_quantity']
                    ];
                }, $validatedData['variants'])
            ];
            
            $result = $this->ecommerceService->updateShopifyInventory($validatedData['product_id'], $inventoryData);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Shopify inventory updated successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Shopify inventory update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Shopify inventory: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Sync product to WooCommerce
     */
    public function syncProductToWooCommerce(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string',
                'type' => 'nullable|string',
                'regular_price' => 'required|string',
                'description' => 'nullable|string',
                'short_description' => 'nullable|string',
                'categories' => 'nullable|array',
                'categories.*.id' => 'nullable|integer',
                'images' => 'nullable|array',
                'images.*.src' => 'nullable|url',
                'manage_stock' => 'nullable|boolean',
                'stock_quantity' => 'nullable|integer',
                'sku' => 'nullable|string',
            ]);
            
            $productData = [
                'name' => $validatedData['name'],
                'type' => $validatedData['type'] ?? 'simple',
                'regular_price' => $validatedData['regular_price'],
                'description' => $validatedData['description'] ?? '',
                'short_description' => $validatedData['short_description'] ?? '',
                'categories' => $validatedData['categories'] ?? [],
                'images' => array_map(function($image) {
                    return [
                        'src' => $image['src']
                    ];
                }, $validatedData['images'] ?? []),
                'manage_stock' => $validatedData['manage_stock'] ?? true,
                'stock_quantity' => $validatedData['stock_quantity'] ?? 0,
                'sku' => $validatedData['sku'] ?? ''
            ];
            
            $result = $this->ecommerceService->syncProductToWooCommerce($productData);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Product synced to WooCommerce successfully'
            ]);
        } catch (Exception $e) {
            Log::error('WooCommerce product sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync product to WooCommerce: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update WooCommerce inventory
     */
    public function updateWooCommerceInventory(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'product_id' => 'required|integer',
                'stock_quantity' => 'required|integer',
                'manage_stock' => 'nullable|boolean',
            ]);
            
            $inventoryData = [
                'stock_quantity' => $validatedData['stock_quantity'],
                'manage_stock' => $validatedData['manage_stock'] ?? true
            ];
            
            $result = $this->ecommerceService->updateWooCommerceInventory($validatedData['product_id'], $inventoryData);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'WooCommerce inventory updated successfully'
            ]);
        } catch (Exception $e) {
            Log::error('WooCommerce inventory update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update WooCommerce inventory: ' . $e->getMessage()
            ], 500);
        }
    }
}