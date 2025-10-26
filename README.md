<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

-   [Simple, fast routing engine](https://laravel.com/docs/routing).
-   [Powerful dependency injection container](https://laravel.com/docs/container).
-   Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
-   Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
-   Database agnostic [schema migrations](https://laravel.com/docs/migrations).
-   [Robust background job processing](https://laravel.com/docs/queues).
-   [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Inventory Management System API

This Laravel application serves as the backend for an inventory management system with a comprehensive API for third-party integrations.

### API Documentation

For detailed API documentation, please refer to:
- [API_DOCUMENTATION.md](API_DOCUMENTATION.md) - Complete API endpoints and usage (English)
- [API_DOCUMENTATION_KHMER.md](API_DOCUMENTATION_KHMER.md) - Complete API endpoints and usage (Khmer)
- [THIRD_PARTY_INTEGRATION_KHMER.md](THIRD_PARTY_INTEGRATION_KHMER.md) - Third-party integration guide (Khmer)
- [SCHEDULED_REPORTS.md](SCHEDULED_REPORTS.md) - Scheduled reporting system documentation
- [MAINTENANCE.md](MAINTENANCE.md) - System maintenance guide
- [INTEGRATION_ENHANCEMENTS.md](INTEGRATION_ENHANCEMENTS.md) - Enhanced integration capabilities documentation
- [INTEGRATION_SUMMARY.md](INTEGRATION_SUMMARY.md) - Summary of all integration enhancements

### Key Features

1. Product Management with batch tracking and automated reordering
2. Inventory tracking with low stock alerts
3. Order and import management
4. Customer and supplier management
5. Staff management
6. Comprehensive reporting system
7. Scheduled reports with automated generation
8. Third-party integration endpoints
9. User authentication and role-based access control
10. Automated database backups
11. System monitoring and health checks
12. Log management and cleanup
13. Performance optimization
14. Payment gateway integration (PayPal, Stripe)
15. Accounting software integration (QuickBooks, Xero)
16. E-commerce platform integration (Shopify, WooCommerce)
17. SMS & Email notification services (Twilio, SendGrid)

## System Maintenance

The system includes comprehensive maintenance features to ensure optimal performance:

- **Automated Database Backups** - Daily backups with compression and retention management
- **System Monitoring** - Hourly health checks with logging and alerting
- **Log Management** - Automatic cleanup of old log files
- **Performance Optimization** - Database indexing and caching strategies
- **Error Reporting** - Comprehensive logging and monitoring
- **Update Management** - Version-controlled database migrations

For detailed maintenance procedures, see [MAINTENANCE.md](MAINTENANCE.md).

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

-   **[Vehikl](https://vehikl.com)**
-   **[Tighten Co.](https://tighten.co)**
-   **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
-   **[64 Robots](https://64robots.com)**
-   **[Curotec](https://www.curotec.com/services/technologies/laravel)**
-   **[DevSquad](https://devsquad.com/hire-laravel-developers)**
-   **[Redberry](https://redberry.international/laravel-development)**
-   **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

<!-- cd c:\xampp\htdocs\project_SE\invetory_api; php make_admin.php -->

Email: admin1999@gmail.com
Password: khin1999

cd c:\xampp\htdocs\project_SE\invetory_api
php artisan tinker

# Create new admin user

$user = App\Models\User::create([
'name' => 'KHIN Admin ',
'email' => 'admin1999@gmail.com',
'password' => Hash::make('khin1999'),
'type' => 'admin'
]);

# Create profile (optional)

App\Models\Profile::create([
'user_id' => $user->id,
'phone' => '+1234567890',
'address' => 'pp'
]);

Method 3: Upgrade Existing User to Admin
If you already have a regular user account, you can upgrade it:

Using Laravel Tinker:

cd c:\xampp\htdocs\project_SE\invetory_api
php artisan tinker

# Find and upgrade user

$user = App\Models\User::where('email', 'existing@email.com')->first();
$user->update(['type' => 'admin']);

php artisan tinker

$user = App\Models\User::create(['name' => 'Your Name', 'email' => 'youremail@domain.com', 'password' => Hash::make('your_password'), 'type' => 'admin']);

$user = App\Models\User::create([
'name' => 'Admin User',
'email' => 'newadmin@inventory.com',
'password' => Hash::make('newpassword123'),
'type' => 'admin'
]);

$user = App\Models\User::create(['name' => 'New Admin', 'email' => 'newadmin@inventory.com', 'password' => Hash::make('admin123'), 'type' => 'admin']);
echo "Created user: " . $user->name . " with email: " . $user->email;

c:\xampp\htdocs\project_SE\invetory_api && php create_new_admin.php