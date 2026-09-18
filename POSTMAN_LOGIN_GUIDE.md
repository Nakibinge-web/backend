# Testing API Routes with Postman (Sanctum Token Auth)

## How it works

- `POST /api/login` and `POST /api/register` are public — no token needed
- Every other route requires a Bearer token in the `Authorization` header
- The token is returned when you log in and revoked when you log out

Base URL: `http://localhost:8000`

---

## Step 1 — Start the server

```bash
cd backend
php artisan serve
```

---

## Step 2 — Create a user (if you don't have one)

**POST** `http://localhost:8000/api/register`

Headers:
```
Accept: application/json
Content-Type: application/json
```

Body (raw JSON):
```json
{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Or use Tinker:
```bash
php artisan tinker
```
```php
$tenant = \App\Models\Tenant::create(['name' => 'Test Business', 'email' => 'test@example.com']);
\App\Models\User::create([
    'tenant_id' => $tenant->id,
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password123'),
]);
```

---

## Step 3 — Login and get your token

**POST** `http://localhost:8000/api/login`

Headers:
```
Accept: application/json
Content-Type: application/json
```

Body (raw JSON):
```json
{
  "email": "test@example.com",
  "password": "password123"
}
```

Success response (200):
```json
{
  "success": true,
  "user": { "id": 1, "name": "Test User", ... },
  "token": "1|abc123xyz...",
  "message": "Login successful."
}
```

Copy the `token` value — you'll use it for all protected requests.

Failed response (401):
```json
{
  "success": false,
  "message": "The provided credentials do not match our records."
}
```

---

## Step 4 — Use the token on protected routes

Add this header to every protected request:

```
Authorization: Bearer 1|abc123xyz...
```

Example — get all products:

**GET** `http://localhost:8000/api/products?tenant_id=1`

Headers:
```
Accept: application/json
Authorization: Bearer 1|abc123xyz...
```

---

## Step 5 — Logout (revokes the token)

**POST** `http://localhost:8000/api/logout`

Headers:
```
Accept: application/json
Authorization: Bearer 1|abc123xyz...
```

Success response:
```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

After logout, the token is deleted from the database and can no longer be used.

---

## Postman tip — save the token automatically

In your Login request, go to the **Tests** tab and add:

```javascript
const res = pm.response.json();
if (res.token) {
    pm.collectionVariables.set("token", res.token);
}
```

Then in all other requests, set the Authorization header to:
```
Bearer {{token}}
```

This way you never have to copy-paste the token manually.

---

## Route summary

| Method | URL | Auth required |
|--------|-----|---------------|
| POST | `/api/login` | No |
| POST | `/api/register` | No |
| POST | `/api/logout` | Yes |
| GET | `/api/products` | Yes |
| GET | `/api/sales` | Yes |
| GET | `/api/purchases` | Yes |
| ... | all other routes | Yes |
