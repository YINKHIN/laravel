# Inventory Management System API Documentation

## Overview
This API provides a comprehensive interface for managing inventory, products, orders, and reports. It includes endpoints for third-party integrations and scheduled reporting capabilities.

## Authentication
All API endpoints (except authentication endpoints) require a valid authentication token.

### Login
```
POST /api/auth/login
```
Parameters:
- `email` (required): User email
- `password` (required): User password

Response:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "User Name",
      "email": "user@example.com",
      "type": "admin"
    },
    "token": "1234567890abcdef"
  }
}
```

### Register
```
POST /api/auth/register
```
Parameters:
- `name` (required): User name
- `email` (required): User email
- `password` (required): User password
- `password_confirmation` (required): Password confirmation

## Third-Party Integration Endpoints

### Enhanced Payment Gateways
- PayPal Integration
- Stripe Integration

### Accounting Software Integration
- QuickBooks Integration
- Xero Integration

### E-commerce Platform Integration
- Shopify Integration
- WooCommerce Integration

### Notification Services
- Twilio SMS Integration
- SendGrid Email Integration

## Third-Party Integration Endpoints

### Product Management

#### Get All Products
```
GET /api/products
```
Parameters:
- `search` (optional): Search term for product name
- `category_id` (optional): Filter by category ID
- `status` (optional): Filter by status (active/inactive)
- `low_stock` (optional): Filter for low stock items (true/false)
- `expired` (optional): Filter for expired products (true/false)
- `near_expiration` (optional): Filter for near expiration products (true/false)

#### Get Product by ID
```
GET /api/products/{id}
```

#### Create Product
```
POST /api/products
```
Parameters:
- `category_id` (required): Category ID
- `brand_id` (optional): Brand ID
- `pro_name` (required): Product name
- `pro_description` (optional): Product description
- `upis` (required): Unit price in stock
- `sup` (required): Supplier unit price
- `qty` (required): Quantity
- `status` (optional): Status (active/inactive)
- `reorder_point` (optional): Reorder point threshold
- `reorder_quantity` (optional): Reorder quantity
- `batch_number` (optional): Batch number
- `expiration_date` (optional): Expiration date (YYYY-MM-DD)
- `image` (optional): Product image file

#### Update Product
```
PUT /api/products/{id}
```
Parameters: Same as Create Product

#### Delete Product
```
DELETE /api/products/{id}
```

#### Get Low Stock Products
```
GET /api/products/get-low-stock-products
```
Parameters:
- `search` (optional): Search term for product name

#### Get Expired Products
```
GET /api/products/get-expired-products
```
Parameters:
- `search` (optional): Search term for product name

#### Get Near Expiration Products
```
GET /api/products/get-near-expiration-products
```
Parameters:
- `search` (optional): Search term for product name

### Category Management

#### Get All Categories
```
GET /api/categories
```

#### Get Category by ID
```
GET /api/categories/{id}
```

#### Create Category
```
POST /api/categories
```
Parameters:
- `name` (required): Category name
- `description` (optional): Category description

#### Update Category
```
PUT /api/categories/{id}
```
Parameters:
- `name` (required): Category name
- `description` (optional): Category description

#### Delete Category
```
DELETE /api/categories/{id}
```

### Brand Management

#### Get All Brands
```
GET /api/brands
```

#### Get Brand by ID
```
GET /api/brands/{id}
```

#### Create Brand
```
POST /api/brands
```
Parameters:
- `name` (required): Brand name
- `description` (optional): Brand description

#### Update Brand
```
PUT /api/brands/{id}
```
Parameters:
- `name` (required): Brand name
- `description` (optional): Brand description

#### Delete Brand
```
DELETE /api/brands/{id}
```

### Supplier Management

#### Get All Suppliers
```
GET /api/suppliers
```

#### Get Supplier by ID
```
GET /api/suppliers/{id}
```

#### Create Supplier
```
POST /api/suppliers
```
Parameters:
- `supplier` (required): Supplier name
- `sup_con` (required): Supplier contact
- `sup_add` (required): Supplier address

#### Update Supplier
```
PUT /api/suppliers/{id}
```
Parameters:
- `supplier` (required): Supplier name
- `sup_con` (required): Supplier contact
- `sup_add` (required): Supplier address

#### Delete Supplier
```
DELETE /api/suppliers/{id}
```

### Customer Management

#### Get All Customers
```
GET /api/customers
```

#### Get Customer by ID
```
GET /api/customers/{id}
```

#### Create Customer
```
POST /api/customers
```
Parameters:
- `cus_name` (required): Customer name
- `cus_con` (required): Customer contact
- `cus_add` (required): Customer address

#### Update Customer
```
PUT /api/customers/{id}
```
Parameters:
- `cus_name` (required): Customer name
- `cus_con` (required): Customer contact
- `cus_add` (required): Customer address

#### Delete Customer
```
DELETE /api/customers/{id}
```

### Staff Management

#### Get All Staff
```
GET /api/staffs
```

#### Get Staff by ID
```
GET /api/staffs/{id}
```

#### Create Staff
```
POST /api/staffs
```
Parameters:
- `full_name` (required): Staff full name
- `gender` (required): Staff gender
- `position` (required): Staff position
- `salary` (required): Staff salary
- `photo` (optional): Staff photo

#### Update Staff
```
PUT /api/staffs/{id}
```
Parameters:
- `full_name` (required): Staff full name
- `gender` (required): Staff gender
- `position` (required): Staff position
- `salary` (required): Staff salary
- `photo` (optional): Staff photo

#### Delete Staff
```
DELETE /api/staffs/{id}
```

### Order Management

#### Get All Orders
```
GET /api/orders
```

#### Get Order by ID
```
GET /api/orders/{id}
```

#### Create Order
```
POST /api/orders
```
Parameters:
- `staff_id` (required): Staff ID
- `cus_id` (required): Customer ID
- `ord_date` (required): Order date (YYYY-MM-DD)
- `total` (required): Order total amount

#### Update Order
```
PUT /api/orders/{id}
```
Parameters:
- `staff_id` (required): Staff ID
- `cus_id` (required): Customer ID
- `ord_date` (required): Order date (YYYY-MM-DD)
- `total` (required): Order total amount

#### Delete Order
```
DELETE /api/orders/{id}
```

#### Force Delete Order
```
DELETE /api/orders/{id}/force
```

### Import Management

#### Get All Imports
```
GET /api/imports
```

#### Get Import by ID
```
GET /api/imports/{id}
```

#### Create Import
```
POST /api/imports
```
Parameters:
- `staff_id` (required): Staff ID
- `sup_id` (required): Supplier ID
- `imp_date` (required): Import date (YYYY-MM-DD)
- `total` (required): Import total amount

#### Update Import
```
PUT /api/imports/{id}
```
Parameters:
- `staff_id` (required): Staff ID
- `sup_id` (required): Supplier ID
- `imp_date` (required): Import date (YYYY-MM-DD)
- `total` (required): Import total amount

#### Delete Import
```
DELETE /api/imports/{id}
```

### Payment Management

#### Get All Payments
```
GET /api/payments
```

#### Get Payment by ID
```
GET /api/payments/{id}
```

#### Get Pending Payments
```
GET /api/payments/pending
```

#### Get Payment Summary
```
GET /api/payments/summary
```

#### Get Order Payment Status
```
GET /api/payments/order/{orderId}/status
```

#### Create Payment
```
POST /api/payments
```
Parameters:
- `order_id` (required): Order ID
- `amount` (required): Payment amount
- `payment_method` (required): Payment method
- `payment_date` (required): Payment date (YYYY-MM-DD)

#### Update Payment
```
PUT /api/payments/{id}
```
Parameters:
- `order_id` (required): Order ID
- `amount` (required): Payment amount
- `payment_method` (required): Payment method
- `payment_date` (required): Payment date (YYYY-MM-DD)

#### Delete Payment
```
DELETE /api/payments/{id}
```

### User Management

#### Get All Users
```
GET /api/users
```

#### Get User by ID
```
GET /api/users/{id}
```

#### Create User
```
POST /api/users
```
Parameters:
- `name` (required): User name
- `email` (required): User email
- `password` (required): User password
- `type` (required): User type (admin/manager/user)
- `phone` (optional): User phone
- `address` (optional): User address

#### Update User
```
PUT /api/users/{id}
```
Parameters:
- `name` (required): User name
- `email` (required): User email
- `type` (required): User type (admin/manager/user)
- `phone` (optional): User phone
- `address` (optional): User address

#### Delete User
```
DELETE /api/users/{id}
```

### Report Management

#### Get Import Report
```
GET /api/reports/import-report
```
Parameters:
- `staff_id` (optional): Filter by staff ID
- `supplier_id` (optional): Filter by supplier ID
- `product_id` (optional): Filter by product ID
- `date_from` (optional): Start date (YYYY-MM-DD)
- `date_to` (optional): End date (YYYY-MM-DD)

#### Get Sales Report
```
GET /api/reports/sales-report
```
Parameters:
- `staff_id` (optional): Filter by staff ID
- `customer_id` (optional): Filter by customer ID
- `product_id` (optional): Filter by product ID
- `date_from` (optional): Start date (YYYY-MM-DD)
- `date_to` (optional): End date (YYYY-MM-DD)

#### Get Import Summary
```
GET /api/reports/import-summary
```
Parameters:
- `date_from` (optional): Start date (YYYY-MM-DD)
- `date_to` (optional): End date (YYYY-MM-DD)

#### Get Sales Summary
```
GET /api/reports/sales-summary
```
Parameters:
- `date_from` (optional): Start date (YYYY-MM-DD)
- `date_to` (optional): End date (YYYY-MM-DD)

#### Export Import Report to Excel
```
GET /api/reports/export-import-excel
```
Parameters:
- `staff_id` (optional): Filter by staff ID
- `supplier_id` (optional): Filter by supplier ID
- `product_id` (optional): Filter by product ID
- `date_from` (optional): Start date (YYYY-MM-DD)
- `date_to` (optional): End date (YYYY-MM-DD)

#### Export Sales Report to Excel
```
GET /api/reports/export-sales-excel
```
Parameters:
- `staff_id` (optional): Filter by staff ID
- `customer_id` (optional): Filter by customer ID
- `product_id` (optional): Filter by product ID
- `date_from` (optional): Start date (YYYY-MM-DD)
- `date_to` (optional): End date (YYYY-MM-DD)

#### Export Import Report to PDF
```
GET /api/reports/export-import-pdf
```
Parameters:
- `staff_id` (optional): Filter by staff ID
- `supplier_id` (optional): Filter by supplier ID
- `product_id` (optional): Filter by product ID
- `date_from` (optional): Start date (YYYY-MM-DD)
- `date_to` (optional): End date (YYYY-MM-DD)

#### Export Sales Report to PDF
```
GET /api/reports/export-sales-pdf
```
Parameters:
- `staff_id` (optional): Filter by staff ID
- `customer_id` (optional): Filter by customer ID
- `product_id` (optional): Filter by product ID
- `date_from` (optional): Start date (YYYY-MM-DD)
- `date_to` (optional): End date (YYYY-MM-DD)

#### Export Single Import Record to Word
```
GET /api/reports/export-single-import-word
```
Parameters:
- `import_id` (required): Import record ID

#### Export Single Sales Record to Word
```
GET /api/reports/export-single-sales-word
```
Parameters:
- `order_id` (required): Order record ID

### Scheduled Reports

#### Get Best Selling Products
```
GET /api/reports/best-selling-products
```
Parameters:
- `period` (optional): daily, weekly, monthly, yearly (default: daily)
- `limit` (optional): Number of products to return (default: 10)

#### Get Low Stock Products
```
GET /api/reports/low-stock-products
```
Parameters:
- `threshold` (optional): Minimum stock threshold (default: 10)

#### Get Inventory Summary
```
GET /api/reports/inventory-summary
```
Parameters:
- `period` (optional): daily, weekly, monthly, yearly (default: daily)

### Notification Management

#### Get All Notifications
```
GET /api/notifications
```

#### Get Unread Notification Count
```
GET /api/notifications/unread-count
```

#### Get Notification by ID
```
GET /api/notifications/{id}
```

#### Create Notification
```
POST /api/notifications
```
Parameters:
- `title` (required): Notification title
- `message` (required): Notification message
- `user_id` (optional): User ID (if not provided, notification is for all users)

#### Update Notification
```
PUT /api/notifications/{id}
```
Parameters:
- `title` (required): Notification title
- `message` (required): Notification message
- `user_id` (optional): User ID

#### Delete Notification
```
DELETE /api/notifications/{id}
```

#### Mark Notification as Read
```
POST /api/notifications/{id}/mark-as-read
```

#### Mark All Notifications as Read
```
POST /api/notifications/mark-all-as-read
```

## Error Responses

All error responses follow this format:
```json
{
  "success": false,
  "message": "Error description"
}
```

Common HTTP status codes:
- 400: Bad Request - Invalid parameters
- 401: Unauthorized - Missing or invalid authentication token
- 403: Forbidden - Insufficient permissions
- 404: Not Found - Resource not found
- 500: Internal Server Error - Server error

## Rate Limiting

The API implements rate limiting to prevent abuse:
- 60 requests per minute for authenticated users
- 10 requests per minute for unauthenticated users

## Webhooks

The system supports webhooks for real-time notifications:
- Order created
- Product low stock
- Product expired
- Payment received

To set up webhooks, contact the system administrator.

## Data Export Formats

The API supports multiple data export formats:
- JSON (default for API responses)
- CSV (for Excel exports)
- PDF (for printable reports)
- DOCX (for Word documents)

## Versioning

The API follows semantic versioning. Currently at version 1.0.0.

## Support

For API support, contact the system administrator or refer to the project documentation.