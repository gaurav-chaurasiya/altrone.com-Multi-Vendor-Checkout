# Multi-Vendor Checkout & Order Engine

A production-ready Laravel implementation of a multi-vendor marketplace checkout system.

## Setup Instructions

1.  **Clone and Install Dependencies**:
    ```bash
    composer install
    ```

2.  **Environment Setup**:
    ```bash
    cp .env.example .env
    # Update DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env
    php artisan key:generate
    ```

3.  **Migrations & Seeding**:
    ```bash
    php artisan migrate:fresh --seed --seeder=MarketplaceSeeder
    ```

4.  **Run Tests**:
    ```bash
    php artisan test tests/Feature/CheckoutTest.php
    ```

## Sample Credentials

| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin** | `admin@example.com` | `password` |
| **Customer 1** | `customer1@example.com` | `password` |
| **Customer 2** | `customer2@example.com` | `password` |

## API Testing (CURL)

### 1. Login

**Customer Login**:
```bash
curl --location 'http://127.0.0.1:8000/api/login' \
--header 'Content-Type: application/json' \
--header 'Accept: application/json' \
--data-raw '{"email":"customer1@example.com","password":"password"}'
```

**Admin Login**:
```bash
curl --location 'http://127.0.0.1:8000/api/login' \
--header 'Content-Type: application/json' \
--header 'Accept: application/json' \
--data-raw '{"email":"admin@example.com","password":"password"}'
```

---

### 2. Marketplace Flow (Customer)

> [!NOTE]
> Copy the `access_token` from the login response and use it as `BEARER_TOKEN` below.

**Add to Cart (Electronics Corp Product)**:
```bash
curl -X POST http://127.0.0.1:8000/api/cart \
     -H "Authorization: Bearer BEARER_TOKEN" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{"product_id": 1, "quantity": 1}'
```

**Add to Cart (Fashion Hub Product)**:
```bash
curl -X POST http://127.0.0.1:8000/api/cart \
     -H "Authorization: Bearer BEARER_TOKEN" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{"product_id": 5, "quantity": 1}'
```

**View Grouped Cart**:
```bash
curl -X GET http://127.0.0.1:8000/api/cart \
     -H "Authorization: Bearer BEARER_TOKEN" \
     -H "Accept: application/json"
```

**Checkout (Splits Orders)**:
```bash
curl -X POST http://127.0.0.1:8000/api/checkout \
     -H "Authorization: Bearer BEARER_TOKEN" \
     -H "Accept: application/json"
```

---

### 3. Order Management (Admin)

**View All Marketplace Orders**:
```bash
curl -X GET http://127.0.0.1:8000/api/admin/orders \
     -H "Authorization: Bearer ADMIN_TOKEN" \
     -H "Accept: application/json"
```

## Architecture Highlights

-   **Order Splitting**: Orders are split per vendor during checkout for independent fulfillment.
-   **Inventory Protection**: Uses DB transactions and `lockForUpdate` to prevent race conditions.
-   **Service Layer**: Business logic is decoupled from controllers into `CartService` and `CheckoutService`.
-   **Scheduled Jobs**: `CancelUnpaidOrders` cleans up abandoned carts every 30 minutes.

## Features
- **Shared Cart**: Customers can add products from multiple vendors into one cart.
- **Order Splitting**: During checkout, the cart is split into separate vendor-wise orders.
- **Stock Management**: Inventory is deducted using DB transactions and row-level locking (`lockForUpdate`) to prevent race conditions.
- **Service Layer**: Business logic is encapsulated in `CartService` and `CheckoutService`.
- **Event-Driven**: Fires `OrderPlaced` events for post-checkout processing (e.g., email notifications).
- **Admin Dashboard**: Admin can monitor all orders across vendors and customers.
- **Scheduled Maintenance**: Automatic cancellation of unpaid orders after 30 minutes.
