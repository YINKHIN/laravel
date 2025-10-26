<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    protected $twilioSid;
    protected $twilioToken;
    protected $twilioFromNumber;
    protected $sendgridApiKey;
    
    public function __construct()
    {
        $this->twilioSid = env('TWILIO_SID');
        $this->twilioToken = env('TWILIO_TOKEN');
        $this->twilioFromNumber = env('TWILIO_FROM_NUMBER');
        
        $this->sendgridApiKey = env('SENDGRID_API_KEY');
    }
    
    /**
     * Send SMS via Twilio
     */
    public function sendSms($to, $message)
    {
        if (!$this->twilioSid || !$this->twilioToken || !$this->twilioFromNumber) {
            throw new Exception('Twilio credentials not configured');
        }
        
        try {
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->twilioSid}/Messages.json";
            
            $response = Http::withBasicAuth($this->twilioSid, $this->twilioToken)
                ->asForm()
                ->post($url, [
                    'From' => $this->twilioFromNumber,
                    'To' => $to,
                    'Body' => $message
                ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('Twilio SMS sending failed', $response->json());
            throw new Exception('Failed to send SMS: ' . $response->json()['message'] ?? 'Unknown error');
        } catch (Exception $e) {
            Log::error('Twilio SMS sending error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Send email via SendGrid
     */
    public function sendEmail($to, $subject, $content, $from = null)
    {
        if (!$this->sendgridApiKey) {
            throw new Exception('SendGrid API key not configured');
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->sendgridApiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.sendgrid.com/v3/mail/send', [
                'personalizations' => [
                    [
                        'to' => [
                            ['email' => $to]
                        ],
                        'subject' => $subject
                    ]
                ],
                'from' => [
                    'email' => $from ?? env('MAIL_FROM_ADDRESS', 'noreply@inventorysystem.com'),
                    'name' => env('MAIL_FROM_NAME', 'Inventory Management System')
                ],
                'content' => [
                    [
                        'type' => 'text/html',
                        'value' => $content
                    ]
                ]
            ]);
            
            if ($response->successful()) {
                return ['success' => true, 'message' => 'Email sent successfully'];
            }
            
            Log::error('SendGrid email sending failed', $response->json());
            throw new Exception('Failed to send email: ' . $response->json()['errors'][0]['message'] ?? 'Unknown error');
        } catch (Exception $e) {
            Log::error('SendGrid email sending error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Send email via SMTP (fallback)
     */
    public function sendEmailViaSMTP($to, $subject, $content, $from = null)
    {
        try {
            // In a real implementation, you would use Laravel's Mail facade
            // This is a simplified version for demonstration
            Log::info('Email sent via SMTP', [
                'to' => $to,
                'subject' => $subject,
                'content' => $content,
                'from' => $from ?? env('MAIL_FROM_ADDRESS')
            ]);
            
            return ['success' => true, 'message' => 'Email sent via SMTP successfully'];
        } catch (Exception $e) {
            Log::error('SMTP email sending error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Send low stock alert via SMS and email
     */
    public function sendLowStockAlert($product, $recipientPhone, $recipientEmail)
    {
        try {
            // Send SMS alert
            if ($recipientPhone && $this->twilioSid) {
                $smsMessage = "Low Stock Alert: {$product['name']} (SKU: {$product['sku']}) is running low. Current quantity: {$product['quantity']}";
                $this->sendSms($recipientPhone, $smsMessage);
            }
            
            // Send email alert
            if ($recipientEmail && $this->sendgridApiKey) {
                $emailSubject = "Low Stock Alert - {$product['name']}";
                $emailContent = "
                <h2>Low Stock Alert</h2>
                <p>Product: {$product['name']}</p>
                <p>SKU: {$product['sku']}</p>
                <p>Current Quantity: {$product['quantity']}</p>
                <p>Reorder Point: {$product['reorder_point']}</p>
                <p>Please reorder this product as soon as possible.</p>
                ";
                
                $this->sendEmail($recipientEmail, $emailSubject, $emailContent);
            }
            
            return ['success' => true, 'message' => 'Low stock alerts sent successfully'];
        } catch (Exception $e) {
            Log::error('Low stock alert sending error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Send order confirmation via SMS and email
     */
    public function sendOrderConfirmation($order, $customerPhone, $customerEmail)
    {
        try {
            // Send SMS confirmation
            if ($customerPhone && $this->twilioSid) {
                $smsMessage = "Order Confirmation: Your order #{$order['id']} has been placed successfully. Total: \${$order['total']}. Thank you!";
                $this->sendSms($customerPhone, $smsMessage);
            }
            
            // Send email confirmation
            if ($customerEmail && $this->sendgridApiKey) {
                $emailSubject = "Order Confirmation - #{$order['id']}";
                $emailContent = "
                <h2>Order Confirmation</h2>
                <p>Order ID: {$order['id']}</p>
                <p>Date: {$order['date']}</p>
                <p>Total: \${$order['total']}</p>
                <h3>Items:</h3>
                <ul>
                " . implode('', array_map(function($item) {
                    return "<li>{$item['name']} - Qty: {$item['quantity']} - Price: \${$item['price']}</li>";
                }, $order['items'])) . "
                </ul>
                <p>Thank you for your order!</p>
                ";
                
                $this->sendEmail($customerEmail, $emailSubject, $emailContent);
            }
            
            return ['success' => true, 'message' => 'Order confirmation sent successfully'];
        } catch (Exception $e) {
            Log::error('Order confirmation sending error: ' . $e->getMessage());
            throw $e;
        }
    }
}