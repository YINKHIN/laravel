<?php

namespace App\Http\Controllers;

use App\Services\AccountingIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class AccountingIntegrationController extends Controller
{
    protected $accountingService;
    
    public function __construct(AccountingIntegrationService $accountingService)
    {
        $this->accountingService = $accountingService;
    }
    
    /**
     * Create QuickBooks invoice
     */
    public function createQuickBooksInvoice(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'customer_id' => 'required|string',
                'line_items' => 'required|array',
                'line_items.*.description' => 'required|string',
                'line_items.*.amount' => 'required|numeric',
                'line_items.*.quantity' => 'required|integer',
                'due_date' => 'nullable|date',
                'memo' => 'nullable|string',
            ]);
            
            $invoiceData = [
                'CustomerRef' => [
                    'value' => $validatedData['customer_id']
                ],
                'Line' => array_map(function($item) {
                    return [
                        'Description' => $item['description'],
                        'Amount' => $item['amount'],
                        'DetailType' => 'SalesItemLineDetail',
                        'SalesItemLineDetail' => [
                            'Qty' => $item['quantity'],
                            'UnitPrice' => $item['amount'] / $item['quantity']
                        ]
                    ];
                }, $validatedData['line_items']),
                'DueDate' => $validatedData['due_date'] ?? now()->addDays(30)->toDateString(),
                'BillEmail' => $validatedData['bill_email'] ?? null,
                'Memo' => $validatedData['memo'] ?? 'Inventory Management System Invoice'
            ];
            
            $result = $this->accountingService->createQuickBooksInvoice($invoiceData);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'QuickBooks invoice created successfully'
            ]);
        } catch (Exception $e) {
            Log::error('QuickBooks invoice creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create QuickBooks invoice: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create QuickBooks customer
     */
    public function createQuickBooksCustomer(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string',
                'email' => 'required|email',
                'phone' => 'nullable|string',
                'billing_address' => 'nullable|array',
                'billing_address.street' => 'nullable|string',
                'billing_address.city' => 'nullable|string',
                'billing_address.state' => 'nullable|string',
                'billing_address.postal_code' => 'nullable|string',
                'billing_address.country' => 'nullable|string',
            ]);
            
            $customerData = [
                'DisplayName' => $validatedData['name'],
                'PrimaryEmailAddr' => [
                    'Address' => $validatedData['email']
                ],
                'PrimaryPhone' => [
                    'FreeFormNumber' => $validatedData['phone'] ?? ''
                ],
                'BillAddr' => [
                    'Line1' => $validatedData['billing_address']['street'] ?? '',
                    'City' => $validatedData['billing_address']['city'] ?? '',
                    'CountrySubDivisionCode' => $validatedData['billing_address']['state'] ?? '',
                    'PostalCode' => $validatedData['billing_address']['postal_code'] ?? '',
                    'Country' => $validatedData['billing_address']['country'] ?? ''
                ]
            ];
            
            $result = $this->accountingService->createQuickBooksCustomer($customerData);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'QuickBooks customer created successfully'
            ]);
        } catch (Exception $e) {
            Log::error('QuickBooks customer creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create QuickBooks customer: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create Xero invoice
     */
    public function createXeroInvoice(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'contact_id' => 'required|string',
                'line_items' => 'required|array',
                'line_items.*.description' => 'required|string',
                'line_items.*.unit_amount' => 'required|numeric',
                'line_items.*.quantity' => 'required|numeric',
                'date' => 'nullable|date',
                'due_date' => 'nullable|date',
                'reference' => 'nullable|string',
            ]);
            
            $invoiceData = [
                'Type' => 'ACCREC',
                'Contact' => [
                    'ContactID' => $validatedData['contact_id']
                ],
                'Date' => $validatedData['date'] ?? now()->toDateString(),
                'DueDate' => $validatedData['due_date'] ?? now()->addDays(30)->toDateString(),
                'LineItems' => array_map(function($item) {
                    return [
                        'Description' => $item['description'],
                        'UnitAmount' => $item['unit_amount'],
                        'Quantity' => $item['quantity']
                    ];
                }, $validatedData['line_items']),
                'Reference' => $validatedData['reference'] ?? 'Inventory Management Invoice',
                'Status' => 'AUTHORISED'
            ];
            
            $result = $this->accountingService->createXeroInvoice($invoiceData);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Xero invoice created successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Xero invoice creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Xero invoice: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create Xero contact
     */
    public function createXeroContact(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string',
                'email_address' => 'required|email',
                'phone_number' => 'nullable|string',
                'addresses' => 'nullable|array',
                'addresses.*.address_type' => 'nullable|string',
                'addresses.*.address_line1' => 'nullable|string',
                'addresses.*.city' => 'nullable|string',
                'addresses.*.region' => 'nullable|string',
                'addresses.*.postal_code' => 'nullable|string',
                'addresses.*.country' => 'nullable|string',
            ]);
            
            $contactData = [
                'Name' => $validatedData['name'],
                'EmailAddress' => $validatedData['email_address'],
                'Phones' => [
                    [
                        'PhoneType' => 'DEFAULT',
                        'PhoneNumber' => $validatedData['phone_number'] ?? ''
                    ]
                ],
                'Addresses' => array_map(function($address) {
                    return [
                        'AddressType' => $address['address_type'] ?? 'POBOX',
                        'AddressLine1' => $address['address_line1'] ?? '',
                        'City' => $address['city'] ?? '',
                        'Region' => $address['region'] ?? '',
                        'PostalCode' => $address['postal_code'] ?? '',
                        'Country' => $address['country'] ?? ''
                    ];
                }, $validatedData['addresses'] ?? [])
            ];
            
            $result = $this->accountingService->createXeroContact($contactData);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Xero contact created successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Xero contact creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Xero contact: ' . $e->getMessage()
            ], 500);
        }
    }
}