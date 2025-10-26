<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    protected $paypalClientId;
    protected $paypalSecret;
    protected $paypalMode;
    protected $stripeSecretKey;
    
    public function __construct()
    {
        $this->paypalClientId = env('PAYPAL_CLIENT_ID');
        $this->paypalSecret = env('PAYPAL_SECRET');
        $this->paypalMode = env('PAYPAL_MODE', 'sandbox');
        $this->stripeSecretKey = env('STRIPE_SECRET_KEY');
    }
    
    /**
     * Get PayPal access token
     */
    public function getPayPalAccessToken()
    {
        if (!$this->paypalClientId || !$this->paypalSecret) {
            throw new Exception('PayPal credentials not configured');
        }
        
        $url = $this->paypalMode === 'sandbox' 
            ? 'https://api.sandbox.paypal.com/v1/oauth2/token' 
            : 'https://api.paypal.com/v1/oauth2/token';
            
        $response = Http::asForm()->withBasicAuth($this->paypalClientId, $this->paypalSecret)
            ->post($url, [
                'grant_type' => 'client_credentials'
            ]);
            
        if ($response->successful()) {
            return $response->json()['access_token'];
        }
        
        Log::error('PayPal token request failed', $response->json());
        throw new Exception('Failed to get PayPal access token');
    }
    
    /**
     * Process PayPal payment
     */
    public function processPayPalPayment($amount, $currency, $description, $returnUrl, $cancelUrl)
    {
        try {
            $accessToken = $this->getPayPalAccessToken();
            
            $url = $this->paypalMode === 'sandbox' 
                ? 'https://api.sandbox.paypal.com/v2/checkout/orders' 
                : 'https://api.paypal.com/v2/checkout/orders';
                
            $response = Http::withToken($accessToken)->post($url, [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', '')
                        ],
                        'description' => $description
                    ]
                ],
                'application_context' => [
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl
                ]
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('PayPal payment creation failed', $response->json());
            throw new Exception('Failed to create PayPal payment');
        } catch (Exception $e) {
            Log::error('PayPal payment processing error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Capture PayPal payment
     */
    public function capturePayPalPayment($orderId)
    {
        try {
            $accessToken = $this->getPayPalAccessToken();
            
            $url = $this->paypalMode === 'sandbox' 
                ? "https://api.sandbox.paypal.com/v2/checkout/orders/{$orderId}/capture" 
                : "https://api.paypal.com/v2/checkout/orders/{$orderId}/capture";
                
            $response = Http::withToken($accessToken)->post($url);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('PayPal payment capture failed', $response->json());
            throw new Exception('Failed to capture PayPal payment');
        } catch (Exception $e) {
            Log::error('PayPal payment capture error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Process Stripe payment
     */
    public function processStripePayment($amount, $currency, $source, $description = null)
    {
        if (!$this->stripeSecretKey) {
            throw new Exception('Stripe credentials not configured');
        }
        
        try {
            $response = Http::withToken($this->stripeSecretKey)->post('https://api.stripe.com/v1/charges', [
                'amount' => $amount * 100, // Stripe expects amount in cents
                'currency' => $currency,
                'source' => $source,
                'description' => $description
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('Stripe payment failed', $response->json());
            throw new Exception('Failed to process Stripe payment: ' . $response->json()['error']['message']);
        } catch (Exception $e) {
            Log::error('Stripe payment processing error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create Stripe payment intent
     */
    public function createStripePaymentIntent($amount, $currency, $metadata = [])
    {
        if (!$this->stripeSecretKey) {
            throw new Exception('Stripe credentials not configured');
        }
        
        try {
            $response = Http::withToken($this->stripeSecretKey)->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => $amount * 100, // Stripe expects amount in cents
                'currency' => $currency,
                'metadata' => $metadata
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('Stripe payment intent creation failed', $response->json());
            throw new Exception('Failed to create Stripe payment intent: ' . $response->json()['error']['message']);
        } catch (Exception $e) {
            Log::error('Stripe payment intent creation error: ' . $e->getMessage());
            throw $e;
        }
    }
}