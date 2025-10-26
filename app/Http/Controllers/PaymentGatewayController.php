<?php

namespace App\Http\Controllers;

use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentGatewayController extends Controller
{
    protected $paymentService;
    
    public function __construct(PaymentGatewayService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    
    /**
     * Process PayPal payment
     */
    public function processPayPalPayment(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'required|string|size:3',
                'description' => 'required|string|max:127',
                'return_url' => 'required|url',
                'cancel_url' => 'required|url',
            ]);
            
            $result = $this->paymentService->processPayPalPayment(
                $validatedData['amount'],
                $validatedData['currency'],
                $validatedData['description'],
                $validatedData['return_url'],
                $validatedData['cancel_url']
            );
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'PayPal payment created successfully'
            ]);
        } catch (Exception $e) {
            Log::error('PayPal payment processing error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process PayPal payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Capture PayPal payment
     */
    public function capturePayPalPayment(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'order_id' => 'required|string',
            ]);
            
            $result = $this->paymentService->capturePayPalPayment($validatedData['order_id']);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'PayPal payment captured successfully'
            ]);
        } catch (Exception $e) {
            Log::error('PayPal payment capture error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to capture PayPal payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Process Stripe payment
     */
    public function processStripePayment(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'required|string|size:3',
                'source' => 'required|string',
                'description' => 'nullable|string|max:255',
            ]);
            
            $result = $this->paymentService->processStripePayment(
                $validatedData['amount'],
                $validatedData['currency'],
                $validatedData['source'],
                $validatedData['description'] ?? null
            );
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Stripe payment processed successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Stripe payment processing error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process Stripe payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create Stripe payment intent
     */
    public function createStripePaymentIntent(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'required|string|size:3',
                'metadata' => 'nullable|array',
            ]);
            
            $result = $this->paymentService->createStripePaymentIntent(
                $validatedData['amount'],
                $validatedData['currency'],
                $validatedData['metadata'] ?? []
            );
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Stripe payment intent created successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Stripe payment intent creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Stripe payment intent: ' . $e->getMessage()
            ], 500);
        }
    }
}