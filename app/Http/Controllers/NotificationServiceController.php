<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationServiceController extends Controller
{
    protected $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    /**
     * Send SMS
     */
    public function sendSms(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'to' => 'required|string',
                'message' => 'required|string|max:1600',
            ]);
            
            $result = $this->notificationService->sendSms($validatedData['to'], $validatedData['message']);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'SMS sent successfully'
            ]);
        } catch (Exception $e) {
            Log::error('SMS sending error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Send email
     */
    public function sendEmail(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'to' => 'required|email',
                'subject' => 'required|string',
                'content' => 'required|string',
                'from' => 'nullable|email',
            ]);
            
            $result = $this->notificationService->sendEmail(
                $validatedData['to'],
                $validatedData['subject'],
                $validatedData['content'],
                $validatedData['from'] ?? null
            );
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Email sent successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Email sending error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Send low stock alert
     */
    public function sendLowStockAlert(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'product' => 'required|array',
                'product.name' => 'required|string',
                'product.sku' => 'required|string',
                'product.quantity' => 'required|integer',
                'product.reorder_point' => 'required|integer',
                'recipient_phone' => 'nullable|string',
                'recipient_email' => 'nullable|email',
            ]);
            
            $result = $this->notificationService->sendLowStockAlert(
                $validatedData['product'],
                $validatedData['recipient_phone'],
                $validatedData['recipient_email']
            );
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Low stock alert sent successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Low stock alert sending error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send low stock alert: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Send order confirmation
     */
    public function sendOrderConfirmation(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'order' => 'required|array',
                'order.id' => 'required|string',
                'order.date' => 'required|string',
                'order.total' => 'required|numeric',
                'order.items' => 'required|array',
                'order.items.*.name' => 'required|string',
                'order.items.*.quantity' => 'required|integer',
                'order.items.*.price' => 'required|numeric',
                'customer_phone' => 'nullable|string',
                'customer_email' => 'nullable|email',
            ]);
            
            $result = $this->notificationService->sendOrderConfirmation(
                $validatedData['order'],
                $validatedData['customer_phone'],
                $validatedData['customer_email']
            );
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Order confirmation sent successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Order confirmation sending error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send order confirmation: ' . $e->getMessage()
            ], 500);
        }
    }
}