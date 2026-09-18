# Inventory and Sales Management System - Backend

Multi-tenant inventory and sales management system built with Laravel 12.

## Project Overview

This is the backend API for the Inventory and Sales Management System. It provides a RESTful API for managing inventory, sales, suppliers, and business reports across multiple tenants (businesses).

## Tech Stack

- Laravel 12
- MySQL Database
- PHP 8.2+

## Database Structure

The system uses a multi-tenant architecture with the following tables:

1. **tenants** - Stores business/organization information
2. **users** - User accounts with role-based access (admin, manager, cashier)
3. **categories** - Product categories
4. **suppliers** - Supplier information
5. **purchases** - Purchase orders from suppliers
6. **products** - Inventory product details
7. **sales** - Sales transactions
8. **sale_items** - Pivot table for products sold in a sale
9. **purchase_items** - Pivot table for products bought in a purchase
10. **stock_movements** - Audit logs for inventory stock changes (IN/OUT)

## Setup Instructions

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL (XAMPP)
- Node.js and npm

### Installation

1. Install PHP dependencies:
```bash
composer install
```

2. Copy environment file:
```bash
cp .env.example .env
```

3. Configure database in `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_and_sales_management
DB_USERNAME=root
DB_PASSWORD=
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Run migrations:
```bash
php artisan migrate
```

6. Start development server:
```bash
php artisan serve
```

## Development Status

Currently implemented:
- Database structure (10 core, sales and inventory tables)
- Multi-tenant architecture foundation
- Product, Sale, and Purchase implementations with M:N pivot relationships

## License

MIT License
