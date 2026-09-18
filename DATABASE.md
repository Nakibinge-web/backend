# Database Documentation

## Database Name
`inventory_and_sales_management`

## Database Type
MySQL

## Multi-Tenant Architecture
This system uses a shared-database multi-tenant architecture. Each business (tenant) has its own records but shares the same database tables. Every business-related table includes a `tenant_id` field to isolate data between tenants.

---

## Tables

### 1. TENANTS
Stores information about each business/organization using the system.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique tenant identifier |
| name | VARCHAR(255) | NOT NULL | Business/organization name |
| email | VARCHAR(255) | NOT NULL, UNIQUE | Business contact email |
| phone | VARCHAR(20) | NULLABLE | Business phone number |
| address | TEXT | NULLABLE | Business physical address |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Purpose:** Central table for multi-tenancy. All other tables reference this table to ensure data isolation.

---

### 2. USERS
Stores user accounts for each tenant with role-based access control.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique user identifier |
| tenant_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References tenants(id) |
| name | VARCHAR(255) | NOT NULL | User's full name |
| email | VARCHAR(255) | NOT NULL | User's email address |
| role | ENUM | NOT NULL, DEFAULT 'cashier' | User role: admin, manager, cashier |
| password | VARCHAR(255) | NOT NULL | Hashed password |
| remember_token | VARCHAR(100) | NULLABLE | Token for "remember me" functionality |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Account creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Foreign Keys:**
- `tenant_id` → `tenants(id)` ON DELETE CASCADE

**Roles:**
- **admin**: Full system access, can manage users, settings, and all operations
- **manager**: Can manage inventory, view reports, process sales and purchases
- **cashier**: Can process sales transactions and view basic inventory

**Purpose:** Manages user authentication and authorization within each tenant.

---

### 3. CATEGORIES
Stores product categories for organizing inventory.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique category identifier |
| tenant_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References tenants(id) |
| name | VARCHAR(255) | NOT NULL | Category name |
| description | TEXT | NULLABLE | Category description |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Foreign Keys:**
- `tenant_id` → `tenants(id)` ON DELETE CASCADE

**Purpose:** Allows tenants to organize products into logical categories (e.g., Electronics, Clothing, Food).

---

### 4. SUPPLIERS
Stores supplier/vendor information for each tenant.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique supplier identifier |
| tenant_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References tenants(id) |
| name | VARCHAR(255) | NOT NULL | Supplier/vendor name |
| contact | VARCHAR(20) | NULLABLE | Supplier contact number |
| email | VARCHAR(255) | NULLABLE | Supplier email address |
| address | TEXT | NULLABLE | Supplier physical address |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Foreign Keys:**
- `tenant_id` → `tenants(id)` ON DELETE CASCADE

**Purpose:** Manages supplier information for tracking where products are purchased from.

---

### 5. PURCHASES
Stores purchase orders from suppliers for each tenant.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique purchase identifier |
| tenant_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References tenants(id) |
| supplier_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References suppliers(id) |
| total_amount | DECIMAL(10,2) | NOT NULL | Total amount of purchase |
| purchase_date | DATE | NOT NULL | Date of purchase |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Foreign Keys:**
- `tenant_id` → `tenants(id)` ON DELETE CASCADE
- `supplier_id` → `suppliers(id)` ON DELETE CASCADE

**Purpose:** Tracks purchase transactions from suppliers for inventory restocking.

---

### 6. PRODUCTS
Stores the product inventory for each tenant.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique product identifier |
| tenant_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References tenants(id) |
| name | VARCHAR(255) | NOT NULL | Product name |
| category_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References categories(id) |
| supplier_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References suppliers(id) |
| stock | INT | DEFAULT 0 | Current stock level |
| price | DECIMAL(10,2) | NOT NULL | Selling price |
| reorder_level | INT | DEFAULT 0 | Level at which to reorder |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Foreign Keys:**
- `tenant_id` → `tenants(id)` ON DELETE CASCADE
- `category_id` → `categories(id)` ON DELETE CASCADE
- `supplier_id` → `suppliers(id)` ON DELETE CASCADE

**Purpose:** Manages product catalog, pricing, and current stock quantity.

---

### 7. SALES
Stores sales transactions by users for each tenant.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique sale identifier |
| tenant_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References tenants(id) |
| user_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References users(id) |
| total_amount | DECIMAL(10,2) | NOT NULL | Total amount of sale |
| payment_method | VARCHAR(255) | NULLABLE | Payment method used |
| sale_date | DATE | NOT NULL | Date of sale |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Foreign Keys:**
- `tenant_id` → `tenants(id)` ON DELETE CASCADE
- `user_id` → `users(id)` ON DELETE CASCADE

**Purpose:** Tracks sales records made by cashiers or managers.

---

### 8. SALE_ITEMS
Pivot table tracking the specific products sold in a sale.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique item identifier |
| tenant_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References tenants(id) |
| sale_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References sales(id) |
| product_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References products(id) |
| quantity | INT | NOT NULL | Quantity sold |
| price | DECIMAL(10,2) | NOT NULL | Price at time of sale |
| subtotal | DECIMAL(10,2) | NOT NULL | quantity * price |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Foreign Keys:**
- `tenant_id` → `tenants(id)` ON DELETE CASCADE
- `sale_id` → `sales(id)` ON DELETE CASCADE
- `product_id` → `products(id)` ON DELETE CASCADE

**Purpose:** Connects sales to products (M:N) and records specific sale prices.

---

### 9. PURCHASE_ITEMS
Pivot table tracking the specific products bought in a purchase.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique item identifier |
| tenant_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References tenants(id) |
| purchase_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References purchases(id) |
| product_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References products(id) |
| quantity | INT | NOT NULL | Quantity purchased |
| cost_price | DECIMAL(10,2) | NOT NULL | Cost price per unit |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Foreign Keys:**
- `tenant_id` → `tenants(id)` ON DELETE CASCADE
- `purchase_id` → `purchases(id)` ON DELETE CASCADE
- `product_id` → `products(id)` ON DELETE CASCADE

**Purpose:** Connects purchases to products (M:N) and records stock intake.

---

### 10. STOCK_MOVEMENTS
Tracks individual stock adjustments over time.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique movement identifier |
| tenant_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References tenants(id) |
| product_id | BIGINT UNSIGNED | FOREIGN KEY, NOT NULL | References products(id) |
| type | ENUM | 'IN' or 'OUT' | Direction of stock movement |
| quantity | INT | NOT NULL | Quantity changed |
| reference_id | VARCHAR(255) | NULLABLE | Associated sale/purchase/reason |
| date | DATE | NOT NULL | Date of movement |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Foreign Keys:**
- `tenant_id` → `tenants(id)` ON DELETE CASCADE
- `product_id` → `products(id)` ON DELETE CASCADE

**Purpose:** Audits every inventory change (purchases, sales, adjustments).

---

## Relationships

```
TENANTS (1) ──────< (Many) USERS, CATEGORIES, SUPPLIERS, PRODUCTS, SALES, PURCHASES, STOCK_MOVEMENTS

CATEGORIES (1) ───< (Many) PRODUCTS
SUPPLIERS (1) ────< (Many) PRODUCTS, PURCHASES
USERS (1) ────────< (Many) SALES
PRODUCTS (1) ─────< (Many) STOCK_MOVEMENTS

SALES (M) ────────< (Pivot) >──────── (N) PRODUCTS (via sale_items)
PURCHASES (M) ────< (Pivot) >──────── (N) PRODUCTS (via purchase_items)
```

---

## Data Isolation Strategy

Each tenant's data is isolated using the `tenant_id` column:

1. All queries must filter by `tenant_id`
2. Foreign key constraints ensure referential integrity
3. CASCADE deletion ensures when a tenant is deleted, all related data is removed
4. Application middleware will automatically scope queries to the authenticated user's tenant

---

## Security Considerations

1. **Password Storage**: User passwords are hashed using bcrypt
2. **Tenant Isolation**: All queries must include tenant_id filtering
3. **Foreign Key Constraints**: Prevent orphaned records and maintain data integrity
4. **Cascade Deletion**: Automatically clean up related data when tenant is removed

---

## Future Tables (Not Yet Implemented)

*No major core tables currently pending. Future phases may introduce reporting, logs or configuration tables.*

---

## Migration Files

Located in: `database/migrations/`

1. `2024_01_01_000001_create_tenants_table.php`
2. `2024_01_01_000002_create_users_table.php`
3. `2024_01_01_000003_create_categories_table.php`
4. `2024_01_01_000004_create_suppliers_table.php`
5. `2026_03_11_000005_create_purchases_table.php`
6. `2026_03_15_005124_create_products_table.php`
7. `2026_03_15_005745_create_sales_table.php`
8. `2026_03_15_010337_create_sale_items_table.php`
9. `2026_03_15_010710_create_purchase_items_table.php`
10. `2026_03_15_011148_create_stock_movements_table.php`

Run migrations with: `php artisan migrate`

---

## SQL Schema Creation

If creating tables manually in MySQL:

```sql
CREATE DATABASE IF NOT EXISTS inventory_and_sales_management;
USE inventory_and_sales_management;

-- See individual table definitions in the Tables section above
```

---

**Last Updated:** March 15, 2026
**Database Version:** 1.1
**Status:** Core, Inventory, and Sales Tables Implemented
