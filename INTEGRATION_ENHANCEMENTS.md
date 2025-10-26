# Inventory Management System - Integration Enhancements

This document describes the newly added integration capabilities for the Inventory Management System.

## Table of Contents
1. [Payment Gateway Integrations](#payment-gateway-integrations)
2. [Accounting Software Integrations](#accounting-software-integrations)
3. [E-commerce Platform Integrations](#e-commerce-platform-integrations)
4. [SMS & Email Notification Services](#sms--email-notification-services)
5. [API Endpoints](#api-endpoints)
6. [Environment Configuration](#environment-configuration)

## Payment Gateway Integrations

### PayPal Integration

The system now supports PayPal payments through the PayPal Checkout API.

**Features:**
- Create PayPal payments
- Capture PayPal payments
- Support for sandbox and live environments

**Requirements:**
- PayPal Business Account
- Client ID and Secret from PayPal Developer Dashboard

### Stripe Integration

The system now supports Stripe payments through the Stripe API.

**Features:**
- Process Stripe payments
- Create Stripe payment intents
- Support for various payment methods

**Requirements:**
- Stripe Account
- Secret Key from Stripe Dashboard

## Accounting Software Integrations

### QuickBooks Integration

The system can now sync data with QuickBooks Online.

**Features:**
- Create invoices in QuickBooks
- Create customers in QuickBooks
- Retrieve customer information

**Requirements:**
- QuickBooks Online Account
- QuickBooks App configured in Developer Dashboard
- OAuth2 credentials

### Xero Integration

The system can now sync data with Xero.

**Features:**
- Create invoices in Xero
- Create contacts in Xero
- Retrieve contact information

**Requirements:**
- Xero Account
- Xero App configured in Developer Dashboard
- OAuth2 credentials

## E-commerce Platform Integrations

### Shopify Integration

The system can now sync products and inventory with Shopify.

**Features:**
- Sync products to Shopify
- Update inventory levels
- Retrieve product information

**Requirements:**
- Shopify Store
- Private App with appropriate permissions
- Access Token

### WooCommerce Integration

The system can now sync products and inventory with WooCommerce.

**Features:**
- Sync products to WooCommerce
- Update inventory levels
- Retrieve product information

**Requirements:**
- WooCommerce Store
- REST API credentials
- Consumer Key and Secret

## SMS & Email Notification Services

### Twilio SMS Integration

The system can now send SMS notifications through Twilio.

**Features:**
- Send SMS alerts for low stock
- Send order confirmations
- Send custom SMS messages

**Requirements:**
- Twilio Account
- Twilio SID and Auth Token
- Twilio Phone Number

### SendGrid Email Integration

The system can now send email notifications through SendGrid.

**Features:**
- Send email alerts for low stock
- Send order confirmations
- Send custom email messages

**Requirements:**
- SendGrid Account
- SendGrid API Key

## API Endpoints

### Payment Gateway Endpoints

#### Process PayPal Payment
```
POST /api/integrations/payments/paypal/process
```
Parameters:
- `amount` (required): Payment amount
- `currency` (required): Currency code (e.g., USD)
- `description` (required): Payment description
- `return_url` (required): URL to redirect after successful payment
- `cancel_url` (required): URL to redirect after cancelled payment

#### Capture PayPal Payment
```
POST /api/integrations/payments/paypal/capture
```
Parameters:
- `order_id` (required): PayPal order ID

#### Process Stripe Payment
```
POST /api/integrations/payments/stripe/process
```
Parameters:
- `amount` (required): Payment amount
- `currency` (required): Currency code (e.g., USD)
- `source` (required): Payment source (token or card ID)
- `description` (optional): Payment description

#### Create Stripe Payment Intent
```
POST /api/integrations/payments/stripe/intent
```
Parameters:
- `amount` (required): Payment amount
- `currency` (required): Currency code (e.g., USD)
- `metadata` (optional): Additional metadata

### Accounting Integration Endpoints

#### Create QuickBooks Invoice
```
POST /api/integrations/accounting/quickbooks/invoice
```
Parameters:
- `customer_id` (required): QuickBooks customer ID
- `line_items` (required): Array of line items
- `due_date` (optional): Invoice due date
- `memo` (optional): Invoice memo

#### Create QuickBooks Customer
```
POST /api/integrations/accounting/quickbooks/customer
```
Parameters:
- `name` (required): Customer name
- `email` (required): Customer email
- `phone` (optional): Customer phone
- `billing_address` (optional): Customer billing address

#### Create Xero Invoice
```
POST /api/integrations/accounting/xero/invoice
```
Parameters:
- `contact_id` (required): Xero contact ID
- `line_items` (required): Array of line items
- `date` (optional): Invoice date
- `due_date` (optional): Invoice due date
- `reference` (optional): Invoice reference

#### Create Xero Contact
```
POST /api/integrations/accounting/xero/contact
```
Parameters:
- `name` (required): Contact name
- `email_address` (required): Contact email
- `phone_number` (optional): Contact phone
- `addresses` (optional): Array of addresses

### E-commerce Integration Endpoints

#### Sync Product to Shopify
```
POST /api/integrations/ecommerce/shopify/sync-product
```
Parameters:
- `title` (required): Product title
- `body_html` (optional): Product description
- `vendor` (optional): Product vendor
- `product_type` (optional): Product type
- `variants` (required): Array of product variants
- `images` (optional): Array of product images

#### Update Shopify Inventory
```
PUT /api/integrations/ecommerce/shopify/inventory/{product_id}
```
Parameters:
- `variants` (required): Array of variants with inventory quantities

#### Sync Product to WooCommerce
```
POST /api/integrations/ecommerce/woocommerce/sync-product
```
Parameters:
- `name` (required): Product name
- `type` (optional): Product type
- `regular_price` (required): Product price
- `description` (optional): Product description
- `short_description` (optional): Product short description
- `categories` (optional): Array of categories
- `images` (optional): Array of images
- `manage_stock` (optional): Whether to manage stock
- `stock_quantity` (optional): Stock quantity
- `sku` (optional): Product SKU

#### Update WooCommerce Inventory
```
PUT /api/integrations/ecommerce/woocommerce/inventory/{product_id}
```
Parameters:
- `stock_quantity` (required): New stock quantity
- `manage_stock` (optional): Whether to manage stock

### Notification Service Endpoints

#### Send SMS
```
POST /api/integrations/notifications/sms/send
```
Parameters:
- `to` (required): Recipient phone number
- `message` (required): SMS message content

#### Send Email
```
POST /api/integrations/notifications/email/send
```
Parameters:
- `to` (required): Recipient email address
- `subject` (required): Email subject
- `content` (required): Email content (HTML)
- `from` (optional): Sender email address

#### Send Low Stock Alert
```
POST /api/integrations/notifications/alerts/low-stock
```
Parameters:
- `product` (required): Product information
- `recipient_phone` (optional): Recipient phone number
- `recipient_email` (optional): Recipient email address

#### Send Order Confirmation
```
POST /api/integrations/notifications/alerts/order-confirmation
```
Parameters:
- `order` (required): Order information
- `customer_phone` (optional): Customer phone number
- `customer_email` (optional): Customer email address

## Environment Configuration

To use these integration features, you need to configure the following environment variables in your `.env` file:

### PayPal Configuration
```env
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_SECRET=your_paypal_secret
PAYPAL_MODE=sandbox # or 'live'
```

### Stripe Configuration
```env
STRIPE_SECRET_KEY=your_stripe_secret_key
```

### QuickBooks Configuration
```env
QUICKBOOKS_CLIENT_ID=your_quickbooks_client_id
QUICKBOOKS_CLIENT_SECRET=your_quickbooks_client_secret
QUICKBOOKS_REALM_ID=your_quickbooks_realm_id
QUICKBOOKS_ACCESS_TOKEN=your_quickbooks_access_token
```

### Xero Configuration
```env
XERO_CLIENT_ID=your_xero_client_id
XERO_CLIENT_SECRET=your_xero_client_secret
XERO_TENANT_ID=your_xero_tenant_id
```

### Shopify Configuration
```env
SHOPIFY_ACCESS_TOKEN=your_shopify_access_token
SHOPIFY_SHOP_NAME=your_shopify_shop_name
```

### WooCommerce Configuration
```env
WOOCOMMERCE_URL=your_woocommerce_url
WOOCOMMERCE_CONSUMER_KEY=your_woocommerce_consumer_key
WOOCOMMERCE_CONSUMER_SECRET=your_woocommerce_consumer_secret
```

### Twilio Configuration
```env
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM_NUMBER=your_twilio_phone_number
```

### SendGrid Configuration
```env
SENDGRID_API_KEY=your_sendgrid_api_key
```

## Implementation Notes

1. All integration services are implemented as separate service classes in the `App\Services` namespace
2. Controllers are created for each integration type to handle API requests
3. All endpoints require authentication through Laravel Sanctum
4. Proper validation is implemented for all request parameters
5. Error handling and logging are implemented for all integration points
6. Environment variables are used for all sensitive configuration data

## Security Considerations

1. All API keys and secrets should be stored securely in environment variables
2. HTTPS should be used for all integration endpoints
3. Proper authentication and authorization should be implemented
4. Rate limiting should be considered for external API calls
5. Sensitive data should not be logged

## Testing

To test these integrations:

1. Configure the required environment variables
2. Use the API endpoints as documented above
3. Check the Laravel logs for any integration errors
4. Verify that data is properly synced between systems

## Support

For issues with these integrations, please check:
1. That all environment variables are properly configured
2. That you have the necessary permissions and credentials
3. The Laravel logs for detailed error messages
4. The documentation for the external services being integrated