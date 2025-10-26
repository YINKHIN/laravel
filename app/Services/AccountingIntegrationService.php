<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AccountingIntegrationService
{
    protected $quickBooksClientId;
    protected $quickBooksClientSecret;
    protected $quickBooksRealmId;
    protected $quickBooksAccessToken;
    protected $xeroClientId;
    protected $xeroClientSecret;
    protected $xeroTenantId;
    
    public function __construct()
    {
        $this->quickBooksClientId = env('QUICKBOOKS_CLIENT_ID');
        $this->quickBooksClientSecret = env('QUICKBOOKS_CLIENT_SECRET');
        $this->quickBooksRealmId = env('QUICKBOOKS_REALM_ID');
        $this->quickBooksAccessToken = env('QUICKBOOKS_ACCESS_TOKEN');
        
        $this->xeroClientId = env('XERO_CLIENT_ID');
        $this->xeroClientSecret = env('XERO_CLIENT_SECRET');
        $this->xeroTenantId = env('XERO_TENANT_ID');
    }
    
    /**
     * Create invoice in QuickBooks
     */
    public function createQuickBooksInvoice($invoiceData)
    {
        if (!$this->quickBooksAccessToken || !$this->quickBooksRealmId) {
            throw new Exception('QuickBooks credentials not configured');
        }
        
        try {
            $url = "https://quickbooks.api.intuit.com/v3/company/{$this->quickBooksRealmId}/invoice";
            
            $response = Http::withToken($this->quickBooksAccessToken)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->post($url, $invoiceData);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('QuickBooks invoice creation failed', $response->json());
            throw new Exception('Failed to create QuickBooks invoice: ' . $response->json()['Fault']['Error'][0]['Message'] ?? 'Unknown error');
        } catch (Exception $e) {
            Log::error('QuickBooks invoice creation error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get QuickBooks customer
     */
    public function getQuickBooksCustomer($customerId)
    {
        if (!$this->quickBooksAccessToken || !$this->quickBooksRealmId) {
            throw new Exception('QuickBooks credentials not configured');
        }
        
        try {
            $url = "https://quickbooks.api.intuit.com/v3/company/{$this->quickBooksRealmId}/customer/{$customerId}";
            
            $response = Http::withToken($this->quickBooksAccessToken)
                ->withHeaders([
                    'Accept' => 'application/json'
                ])
                ->get($url);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('QuickBooks customer retrieval failed', $response->json());
            throw new Exception('Failed to retrieve QuickBooks customer');
        } catch (Exception $e) {
            Log::error('QuickBooks customer retrieval error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create customer in QuickBooks
     */
    public function createQuickBooksCustomer($customerData)
    {
        if (!$this->quickBooksAccessToken || !$this->quickBooksRealmId) {
            throw new Exception('QuickBooks credentials not configured');
        }
        
        try {
            $url = "https://quickbooks.api.intuit.com/v3/company/{$this->quickBooksRealmId}/customer";
            
            $response = Http::withToken($this->quickBooksAccessToken)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->post($url, $customerData);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('QuickBooks customer creation failed', $response->json());
            throw new Exception('Failed to create QuickBooks customer: ' . $response->json()['Fault']['Error'][0]['Message'] ?? 'Unknown error');
        } catch (Exception $e) {
            Log::error('QuickBooks customer creation error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create invoice in Xero
     */
    public function createXeroInvoice($invoiceData)
    {
        if (!$this->xeroClientId || !$this->xeroTenantId) {
            throw new Exception('Xero credentials not configured');
        }
        
        try {
            $response = Http::withToken($this->xeroClientId)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Xero-Tenant-Id' => $this->xeroTenantId
                ])
                ->post('https://api.xero.com/api.xro/2.0/Invoices', $invoiceData);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('Xero invoice creation failed', $response->json());
            throw new Exception('Failed to create Xero invoice');
        } catch (Exception $e) {
            Log::error('Xero invoice creation error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get Xero contact
     */
    public function getXeroContact($contactId)
    {
        if (!$this->xeroClientId || !$this->xeroTenantId) {
            throw new Exception('Xero credentials not configured');
        }
        
        try {
            $response = Http::withToken($this->xeroClientId)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Xero-Tenant-Id' => $this->xeroTenantId
                ])
                ->get("https://api.xero.com/api.xro/2.0/Contacts/{$contactId}");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('Xero contact retrieval failed', $response->json());
            throw new Exception('Failed to retrieve Xero contact');
        } catch (Exception $e) {
            Log::error('Xero contact retrieval error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create contact in Xero
     */
    public function createXeroContact($contactData)
    {
        if (!$this->xeroClientId || !$this->xeroTenantId) {
            throw new Exception('Xero credentials not configured');
        }
        
        try {
            $response = Http::withToken($this->xeroClientId)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Xero-Tenant-Id' => $this->xeroTenantId
                ])
                ->post('https://api.xero.com/api.xro/2.0/Contacts', $contactData);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error('Xero contact creation failed', $response->json());
            throw new Exception('Failed to create Xero contact');
        } catch (Exception $e) {
            Log::error('Xero contact creation error: ' . $e->getMessage());
            throw $e;
        }
    }
}