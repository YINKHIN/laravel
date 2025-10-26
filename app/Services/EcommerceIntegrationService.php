<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EcommerceIntegrationService
{
    protected $shopifyAccessToken;
    protected $shopifyShopName;
    protected $woocommerceUrl;
    protected $woocommerceConsumerKey;
    protected $woocommerceConsumerSecret;
    
    public function __construct()
    {
        $this->shopifyAccessToken = env('SHOPIFY_ACCESS_TOKEN');
        $this->shopifyShopName = env('SHOPIFY_SHOP_NAME');
        
        $this->woocommerceUrl = env('WOOCOMMERCE_URL');
        $this->woocommerceConsumerKey = env('WOOCOMMERCE_CONSUMER_KEY');
        $this->woocommerceConsumerSecret = env('WOOCOMMERCE_CONSUMER_SECRET');
    }
    
    /**
     * Sync product to Shopify
     */
    public function syncProductToShopify($productData)
    {
        if (!$this->shopifyAccessToken || !$this->shopifyShopName) {
            throw new Exception('Shopify credentials not configured');
        }
        
        try {
            $url = "https://{$this->shopifyShopName}.myshopify.com/admin/api/2023-04/products.json";
            
            $response = Http::withToken($this->shopifyAccessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Shopify-Access-Token' => $this->shopifyAccessToken
                ])
                ->post($url, [
                    'product' => $productData
                ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('Shopify product sync failed', $response->json());
            throw new Exception('Failed to sync product to Shopify: ' . $response->json()['errors'] ?? 'Unknown error');
        } catch (Exception $e) {
            Log::error('Shopify product sync error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get Shopify product
     */
    public function getShopifyProduct($productId)
    {
        if (!$this->shopifyAccessToken || !$this->shopifyShopName) {
            throw new Exception('Shopify credentials not configured');
        }
        
        try {
            $url = "https://{$this->shopifyShopName}.myshopify.com/admin/api/2023-04/products/{$productId}.json";
            
            $response = Http::withToken($this->shopifyAccessToken)
                ->withHeaders([
                    'X-Shopify-Access-Token' => $this->shopifyAccessToken
                ])
                ->get($url);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('Shopify product retrieval failed', $response->json());
            throw new Exception('Failed to retrieve Shopify product');
        } catch (Exception $e) {
            Log::error('Shopify product retrieval error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update Shopify product inventory
     */
    public function updateShopifyInventory($productId, $inventoryData)
    {
        if (!$this->shopifyAccessToken || !$this->shopifyShopName) {
            throw new Exception('Shopify credentials not configured');
        }
        
        try {
            $url = "https://{$this->shopifyShopName}.myshopify.com/admin/api/2023-04/products/{$productId}.json";
            
            $response = Http::withToken($this->shopifyAccessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Shopify-Access-Token' => $this->shopifyAccessToken
                ])
                ->put($url, [
                    'product' => $inventoryData
                ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('Shopify inventory update failed', $response->json());
            throw new Exception('Failed to update Shopify inventory: ' . $response->json()['errors'] ?? 'Unknown error');
        } catch (Exception $e) {
            Log::error('Shopify inventory update error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Sync product to WooCommerce
     */
    public function syncProductToWooCommerce($productData)
    {
        if (!$this->woocommerceUrl || !$this->woocommerceConsumerKey || !$this->woocommerceConsumerSecret) {
            throw new Exception('WooCommerce credentials not configured');
        }
        
        try {
            $url = "{$this->woocommerceUrl}/wp-json/wc/v3/products";
            
            $response = Http::withBasicAuth($this->woocommerceConsumerKey, $this->woocommerceConsumerSecret)
                ->withHeaders([
                    'Content-Type' => 'application/json'
                ])
                ->post($url, $productData);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('WooCommerce product sync failed', $response->json());
            throw new Exception('Failed to sync product to WooCommerce: ' . $response->json()['message'] ?? 'Unknown error');
        } catch (Exception $e) {
            Log::error('WooCommerce product sync error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get WooCommerce product
     */
    public function getWooCommerceProduct($productId)
    {
        if (!$this->woocommerceUrl || !$this->woocommerceConsumerKey || !$this->woocommerceConsumerSecret) {
            throw new Exception('WooCommerce credentials not configured');
        }
        
        try {
            $url = "{$this->woocommerceUrl}/wp-json/wc/v3/products/{$productId}";
            
            $response = Http::withBasicAuth($this->woocommerceConsumerKey, $this->woocommerceConsumerSecret)
                ->get($url);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('WooCommerce product retrieval failed', $response->json());
            throw new Exception('Failed to retrieve WooCommerce product');
        } catch (Exception $e) {
            Log::error('WooCommerce product retrieval error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update WooCommerce product inventory
     */
    public function updateWooCommerceInventory($productId, $inventoryData)
    {
        if (!$this->woocommerceUrl || !$this->woocommerceConsumerKey || !$this->woocommerceConsumerSecret) {
            throw new Exception('WooCommerce credentials not configured');
        }
        
        try {
            $url = "{$this->woocommerceUrl}/wp-json/wc/v3/products/{$productId}";
            
            $response = Http::withBasicAuth($this->woocommerceConsumerKey, $this->woocommerceConsumerSecret)
                ->withHeaders([
                    'Content-Type' => 'application/json'
                ])
                ->put($url, $inventoryData);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('WooCommerce inventory update failed', $response->json());
            throw new Exception('Failed to update WooCommerce inventory: ' . $response->json()['message'] ?? 'Unknown error');
        } catch (Exception $e) {
            Log::error('WooCommerce inventory update error: ' . $e->getMessage());
            throw $e;
        }
    }
}