# API Endpoints Documentation

## Base URL
```
http://localhost:8000/api
```

## Response Format
All endpoints return JSON responses in this format:
```json
{
    "success": true/false,
    "message": "Description",
    "data": { ... }
}
```

---

## 🏢 Tenant Management

### List All Tenants
```http
GET /api/tenants
```

### Register New Business
```http
POST /api/tenants
Content-Type: application/json

{
    "name": "ABC Store",
    "email": "abc@store.com",
    "phone": "0712345678",
    "address": "123 Main Street"
}
```

### Get Tenant Details
```http
GET /api/tenants/{id}
```

### Update Tenant
```http
PUT /api/tenants/{id}
Content-Type: application/json

{
    "name": "ABC Store Updated",
    "phone": "0712345679"
}
```

### Delete Tenant
```http
DELETE /api/tenants/{id}
```

---

## 👥 User Management

### List Users for Tenant
```http
GET /api/users?tenant_id=1
```

### Create New User
```http
POST /api/users
Content-Type: application/json

{
    "tenant_id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "cashier",
    "password": "password123"
}
```

### Get Users by Role
```http
GET /api/users/by-role?tenant_id=1&role=cashier
```

### Get User Details
```http
GET /api/users/{id}
```

### Update User
```http
PUT /api/users/{id}
Content-Type: application/json

{
    "name": "John Smith",
    "role": "manager"
}
```

### Delete User
```http
DELETE /api/users/{id}
```

---

## 📂 Category Management

### List Categories for Tenant
```http
GET /api/categories?tenant_id=1
```

### Create Category
```http
POST /api/categories
Content-Type: application/json

{
    "tenant_id": 1,
    "name": "Electronics",
    "description": "Electronic devices and accessories"
}
```

### Get Category Details
```http
GET /api/categories/{id}
```

### Update Category
```http
PUT /api/categories/{id}
Content-Type: application/json

{
    "name": "Electronics & Gadgets",
    "description": "Updated description"
}
```

### Delete Category
```http
DELETE /api/categories/{id}
```

---

## 🏭 Supplier Management

### List Suppliers for Tenant
```http
GET /api/suppliers?tenant_id=1
```

### Create Supplier
```http
POST /api/suppliers
Content-Type: application/json

{
    "tenant_id": 1,
    "name": "Tech Suppliers Ltd",
    "contact": "0700000000",
    "email": "info@techsuppliers.com",
    "address": "456 Supplier Road"
}
```

### Get Supplier Details
```http
GET /api/suppliers/{id}
```

### Update Supplier
```http
PUT /api/suppliers/{id}
Content-Type: application/json

{
    "contact": "0711111111",
    "email": "newemail@techsuppliers.com"
}
```

### Delete Supplier
```http
DELETE /api/suppliers/{id}
```

---

## 📦 Product Management

### List Products for Tenant
```http
GET /api/products?tenant_id=1
```

### Create Product
```http
POST /api/products
Content-Type: application/json

{
    "tenant_id": 1,
    "name": "iPhone 15",
    "category_id": 1,
    "supplier_id": 1,
    "stock": 50,
    "price": 999.99,
    "reorder_level": 10
}
```

### Get Low Stock Products
```http
GET /api/products/low-stock?tenant_id=1
```

### Get Product Details
```http
GET /api/products/{id}
```

### Update Product
```http
PUT /api/products/{id}
Content-Type: application/json

{
    "stock": 45,
    "price": 949.99
}
```

### Delete Product
```http
DELETE /api/products/{id}
```

---

## 💰 Sales Management

### List Sales for Tenant
```http
GET /api/sales?tenant_id=1
```

### Process New Sale
```http
POST /api/sales
Content-Type: application/json

{
    "tenant_id": 1,
    "user_id": 2,
    "payment_method": "cash",
    "items": [
        {
            "product_id": 1,
            "quantity": 2,
            "price": 999.99
        },
        {
            "product_id": 2,
            "quantity": 1,
            "price": 49.99
        }
    ]
}
```

### Get Daily Sales Report
```http
GET /api/sales/daily-report?tenant_id=1&date=2026-03-18
```

### Get Sale Details
```http
GET /api/sales/{id}
```

---

## 🛒 Purchase Management

### List Purchases for Tenant
```http
GET /api/purchases?tenant_id=1
```

### Record New Purchase
```http
POST /api/purchases
Content-Type: application/json

{
    "tenant_id": 1,
    "supplier_id": 1,
    "items": [
        {
            "product_id": 1,
            "quantity": 20,
            "cost_price": 800.00
        },
        {
            "product_id": 2,
            "quantity": 50,
            "cost_price": 30.00
        }
    ]
}
```

### Get Monthly Purchase Report
```http
GET /api/purchases/monthly-report?tenant_id=1&month=2026-03
```

### Get Purchase Details
```http
GET /api/purchases/{id}
```

---

## 📊 Stock Movement Tracking

### List All Stock Movements
```http
GET /api/stock-movements?tenant_id=1
```

### Get Movements for Specific Product
```http
GET /api/stock-movements/product/{productId}?tenant_id=1
```

### Get Movements by Type
```http
GET /api/stock-movements/by-type?tenant_id=1&type=IN
```

### Get Movements by Date Range
```http
GET /api/stock-movements/date-range?tenant_id=1&start_date=2026-03-01&end_date=2026-03-31
```

### Get Movement Details
```http
GET /api/stock-movements/{id}
```

---

## 🔧 Utility Endpoints

### API Health Check
```http
GET /api/health
```

### API Test
```http
GET /api/test
```

---

## 📝 Usage Examples

### Complete Sale Transaction Flow
1. **Check product availability:**
   ```http
   GET /api/products?tenant_id=1
   ```

2. **Process the sale:**
   ```http
   POST /api/sales
   {
       "tenant_id": 1,
       "user_id": 2,
       "payment_method": "cash",
       "items": [{"product_id": 1, "quantity": 1, "price": 999.99}]
   }
   ```

3. **Verify stock was updated:**
   ```http
   GET /api/products/1
   ```

4. **Check stock movement:**
   ```http
   GET /api/stock-movements/product/1?tenant_id=1
   ```

### Complete Purchase Flow
1. **Add supplier if needed:**
   ```http
   POST /api/suppliers
   {
       "tenant_id": 1,
       "name": "New Supplier",
       "contact": "0700000000"
   }
   ```

2. **Record purchase:**
   ```http
   POST /api/purchases
   {
       "tenant_id": 1,
       "supplier_id": 1,
       "items": [{"product_id": 1, "quantity": 10, "cost_price": 800.00}]
   }
   ```

3. **Verify stock increased:**
   ```http
   GET /api/products/1
   ```

---

## 🚀 Testing the API

### Using cURL
```bash
# Test API health
curl http://localhost:8000/api/health

# Create a tenant
curl -X POST http://localhost:8000/api/tenants \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Store","email":"test@store.com"}'

# List products
curl "http://localhost:8000/api/products?tenant_id=1"
```

### Using Postman
1. Import the endpoints into Postman
2. Set base URL: `http://localhost:8000/api`
3. Add `Content-Type: application/json` header for POST/PUT requests
4. Test each endpoint with sample data

---

## 📋 Status Codes

- **200 OK** - Successful GET, PUT requests
- **201 Created** - Successful POST requests
- **422 Unprocessable Entity** - Validation errors
- **404 Not Found** - Resource not found
- **500 Internal Server Error** - Server errors

---

**Total Endpoints:** 35+
**Status:** Ready for testing
**Next Steps:** Add authentication middleware, test all endpoints