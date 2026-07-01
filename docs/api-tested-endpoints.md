# Entouche API — Tested Endpoints

> Auto-generated reference of all endpoints with recorded request/response samples.
> Source: `postman/collections/Entouche API/`

---

## Table of Contents

- [Summary Table](#summary-table)
- [Authentication](#authentication)
  - [POST Login](#post-login)
  - [POST Logout](#post-logout)
  - [POST Refresh Token](#post-refresh-token)
  - [GET Get Current User](#get-get-current-user)
- [Categories](#categories)
  - [GET List Categories](#get-list-categories)
  - [POST Create Category](#post-create-category)
  - [GET Get Category](#get-get-category)
  - [PUT Update Category](#put-update-category)
  - [DELETE Delete Category](#delete-delete-category)
  - [POST Toggle Category Status](#post-toggle-category-status)
- [Items](#items)
  - [GET List Items](#get-list-items)
  - [POST Create Item](#post-create-item)
  - [GET Get Item](#get-get-item)
  - [PUT Update Item](#put-update-item)
  - [DELETE Delete Item](#delete-delete-item)
  - [POST Toggle Item Status](#post-toggle-item-status)
  - [GET Get Item Stock Balance](#get-get-item-stock-balance)
  - [GET Get Item Transactions](#get-get-item-transactions)
  - [POST Upload Item Image](#post-upload-item-image)
  - [DELETE Remove Item Image](#delete-remove-item-image)
- [Roles & Permissions](#roles--permissions)
  - [GET List Roles](#get-list-roles)
  - [GET Get Role](#get-get-role)
  - [GET List Permissions](#get-list-permissions)
- [Suppliers](#suppliers)
  - [GET List Suppliers](#get-list-suppliers)
  - [POST Create Supplier](#post-create-supplier)
  - [GET Get Supplier](#get-get-supplier)
  - [PUT Update Supplier](#put-update-supplier)
  - [DELETE Delete Supplier](#delete-delete-supplier)
  - [POST Toggle Supplier Status](#post-toggle-supplier-status)
- [Units of Measure](#units-of-measure)
  - [GET List Units](#get-list-units)
  - [POST Create Unit](#post-create-unit)
  - [GET Get Unit](#get-get-unit)
  - [PUT Update Unit](#put-update-unit)
  - [DELETE Delete Unit](#delete-delete-unit)
  - [POST Toggle Unit Status](#post-toggle-unit-status)
- [Users](#users)
  - [GET List Users](#get-list-users)
  - [POST Create User](#post-create-user)
  - [GET Get User](#get-get-user)
  - [PUT Update User](#put-update-user)
  - [DELETE Delete User](#delete-delete-user)
  - [POST Toggle User Status](#post-toggle-user-status)
  - [POST Assign Role](#post-assign-role)
  - [POST Remove Role](#post-remove-role)

---

## Summary Table

| # | Group | Method | Path | Examples |
|---|-------|--------|------|----------|
| 1 | Authentication | `POST` | `/api/v1/auth/login` | 1 |
| 2 | Authentication | `POST` | `/api/v1/auth/logout` | 1 |
| 3 | Authentication | `POST` | `/api/v1/auth/refresh` | 1 |
| 4 | Authentication | `GET` | `/api/v1/auth/me` | 1 |
| 5 | Categories | `GET` | `/api/v1/categories` | 2 |
| 6 | Categories | `POST` | `/api/v1/categories` | 1 |
| 7 | Categories | `GET` | `/api/v1/categories/{{category_id}}` | 1 |
| 8 | Categories | `PUT` | `/api/v1/categories/{{category_id}}` | 1 |
| 9 | Categories | `DELETE` | `/api/v1/categories/{{category_id}}` | 1 |
| 10 | Categories | `POST` | `/api/v1/categories/{{category_id}}/toggle-status` | 1 |
| 11 | Items | `GET` | `/api/v1/items` | 1 |
| 12 | Items | `POST` | `/api/v1/items` | 2 |
| 13 | Items | `GET` | `/api/v1/items/{{item_id}}` | 1 |
| 14 | Items | `PUT` | `/api/v1/items/{{item_id}}` | 1 |
| 15 | Items | `DELETE` | `/api/v1/items/{{item_id}}` | 1 |
| 16 | Items | `POST` | `/api/v1/items/{{item_id}}/toggle-status` | 1 |
| 17 | Items | `GET` | `/api/v1/items/{{item_id}}/stock-balance` | 1 |
| 18 | Items | `GET` | `/api/v1/items/{{item_id}}/transactions` | 1 |
| 19 | Items | `POST` | `/api/v1/items/{{item_id}}/image` | 2 |
| 20 | Items | `DELETE` | `/api/v1/items/{{item_id}}/image` | 1 |
| 21 | Roles & Permissions | `GET` | `/api/v1/roles` | 1 |
| 22 | Roles & Permissions | `GET` | `/api/v1/roles/{{role_id}}` | 1 |
| 23 | Roles & Permissions | `GET` | `/api/v1/permissions` | 1 |
| 24 | Suppliers | `GET` | `/api/v1/suppliers` | 1 |
| 25 | Suppliers | `POST` | `/api/v1/suppliers` | 1 |
| 26 | Suppliers | `GET` | `/api/v1/suppliers/{{supplier_id}}` | 1 |
| 27 | Suppliers | `PUT` | `/api/v1/suppliers/{{supplier_id}}` | 1 |
| 28 | Suppliers | `DELETE` | `/api/v1/suppliers/{{supplier_id}}` | 1 |
| 29 | Suppliers | `POST` | `/api/v1/suppliers/{{supplier_id}}/toggle-status` | 2 |
| 30 | Units of Measure | `GET` | `/api/v1/units` | 1 |
| 31 | Units of Measure | `POST` | `/api/v1/units` | 1 |
| 32 | Units of Measure | `GET` | `/api/v1/units/{{unit_id}}` | 1 |
| 33 | Units of Measure | `PUT` | `/api/v1/units/{{unit_id}}` | 1 |
| 34 | Units of Measure | `DELETE` | `/api/v1/units/{{unit_id}}` | 1 |
| 35 | Units of Measure | `POST` | `/api/v1/units/{{unit_id}}/toggle-status` | 1 |
| 36 | Users | `GET` | `/api/v1/users` | 1 |
| 37 | Users | `POST` | `/api/v1/users` | 1 |
| 38 | Users | `GET` | `/api/v1/users/{{user_id}}` | 1 |
| 39 | Users | `PUT` | `/api/v1/users/{{user_id}}` | 1 |
| 40 | Users | `DELETE` | `/api/v1/users/{{user_id}}` | 1 |
| 41 | Users | `POST` | `/api/v1/users/{{user_id}}/toggle-status` | 2 |
| 42 | Users | `POST` | `/api/v1/users/{{user_id}}/assign-role` | 1 |
| 43 | Users | `POST` | `/api/v1/users/{{user_id}}/remove-role` | 1 |

---

## Authentication

### POST Login

**Endpoint:** `POST /api/v1/auth/login`  
**Auth:** None

#### Request

```http
POST {{base_url}}/api/v1/auth/login
Accept: application/json
Content-Type: application/json
```

**Body:**
```json
{
  "email": "admin@inventory.local",
  "password": "Admin@1234"
}
```

#### Response — `200 OK` (success)

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 2,
            "name": "System Administrator",
            "email": "admin@inventory.local",
            "phone": null,
            "status": "active",
            "email_verified_at": null,
            "created_at": "2026-06-30T03:38:09.000000Z",
            "updated_at": "2026-06-30T03:38:09.000000Z",
            "deleted_at": null,
            "roles": [
                {
                    "id": 1,
                    "name": "system_administrator",
                    "guard_name": "api",
                    "created_at": "2026-06-30T03:38:09.000000Z",
                    "updated_at": "2026-06-30T03:38:09.000000Z",
                    "pivot": {
                        "model_type": "App\\Models\\User",
                        "model_id": 2,
                        "role_id": 1
                    },
                    "permissions": [
                        { "id": 1, "name": "items.view", "guard_name": "api" },
                        { "id": 2, "name": "items.create", "guard_name": "api" },
                        { "id": 3, "name": "items.edit", "guard_name": "api" },
                        { "id": 4, "name": "items.delete", "guard_name": "api" },
                        { "id": 5, "name": "receipts.view", "guard_name": "api" },
                        { "id": 6, "name": "receipts.create", "guard_name": "api" },
                        { "id": 7, "name": "receipts.approve", "guard_name": "api" },
                        { "id": 8, "name": "transfers.view", "guard_name": "api" },
                        { "id": 9, "name": "transfers.create", "guard_name": "api" },
                        { "id": 10, "name": "transfers.approve", "guard_name": "api" },
                        { "id": 11, "name": "adjustments.view", "guard_name": "api" },
                        { "id": 12, "name": "adjustments.create", "guard_name": "api" },
                        { "id": 13, "name": "adjustments.approve", "guard_name": "api" },
                        { "id": 14, "name": "stock_counts.view", "guard_name": "api" },
                        { "id": 15, "name": "stock_counts.create", "guard_name": "api" },
                        { "id": 16, "name": "stock_counts.approve", "guard_name": "api" },
                        { "id": 17, "name": "reports.view", "guard_name": "api" },
                        { "id": 18, "name": "data_import.manage", "guard_name": "api" },
                        { "id": 19, "name": "audit_logs.view", "guard_name": "api" },
                        { "id": 20, "name": "users.manage", "guard_name": "api" },
                        { "id": 21, "name": "settings.manage", "guard_name": "api" },
                        { "id": 22, "name": "master-data.manage", "guard_name": "api" }
                    ]
                }
            ]
        },
        "access_token": "<jwt_token>",
        "token_type": "Bearer"
    },
    "errors": null
}
```

---

### POST Logout

**Endpoint:** `POST /api/v1/auth/logout`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/auth/logout
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Logout successful",
  "data": null,
  "errors": null
}
```

---

### POST Refresh Token

**Endpoint:** `POST /api/v1/auth/refresh`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/auth/refresh
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Token refreshed",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer"
  },
  "errors": null
}
```

---

### GET Get Current User

**Endpoint:** `GET /api/v1/auth/me`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/auth/me
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": null,
    "status": "active",
    "email_verified_at": null,
    "created_at": "2026-06-30T03:12:26.000000Z",
    "updated_at": "2026-06-30T03:12:26.000000Z",
    "deleted_at": null,
    "roles": []
  },
  "errors": null
}
```

---

## Categories

### GET List Categories

**Endpoint:** `GET /api/v1/categories`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/categories
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": 1,
      "parent_id": null,
      "name": "Electronics",
      "code": "ELEC",
      "status": "active",
      "created_at": "2026-06-30T14:28:13.000000Z",
      "updated_at": "2026-06-30T14:37:50.000000Z",
      "deleted_at": null
    }
  ],
  "errors": null,
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  },
  "links": {
    "first": "http://127.0.0.1:8000/api/v1/categories?page=1",
    "last": "http://127.0.0.1:8000/api/v1/categories?page=1",
    "prev": null,
    "next": null
  }
}
```

#### Response — `200 OK` (success - empty)

```json
{
  "success": true,
  "message": "Success",
  "data": [],
  "errors": null,
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 0
  },
  "links": {
    "first": "http://127.0.0.1:8000/api/v1/categories?page=1",
    "last": "http://127.0.0.1:8000/api/v1/categories?page=1",
    "prev": null,
    "next": null
  }
}
```

---

### POST Create Category

**Endpoint:** `POST /api/v1/categories`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/categories
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
  "name": "Electronics",
  "code": "ELEC",
  "parent_id": null,
  "status": "active"
}
```

#### Response — `201 Created` (success)

```json
{
  "success": true,
  "message": "Created successfully",
  "data": {
    "parent_id": null,
    "name": "Electronics",
    "code": "ELEC",
    "status": "active",
    "updated_at": "2026-06-30T14:28:13.000000Z",
    "created_at": "2026-06-30T14:28:13.000000Z",
    "id": 1
  },
  "errors": null
}
```

---

### GET Get Category

**Endpoint:** `GET /api/v1/categories/{{category_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/categories/{{category_id}}
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": 1,
    "parent_id": null,
    "name": "Electronics",
    "code": "ELEC",
    "status": "active",
    "created_at": "2026-06-30T14:28:13.000000Z",
    "updated_at": "2026-06-30T14:28:13.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

### PUT Update Category

**Endpoint:** `PUT /api/v1/categories/{{category_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
PUT {{base_url}}/api/v1/categories/{{category_id}}
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
  "name": "Electronics",
  "code": "ELEC",
  "status": "active"
}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Updated",
  "data": {
    "id": 1,
    "parent_id": null,
    "name": "Electronics Updated",
    "code": "ELEC",
    "status": "active",
    "created_at": "2026-06-30T14:28:13.000000Z",
    "updated_at": "2026-06-30T14:34:56.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

### DELETE Delete Category

**Endpoint:** `DELETE /api/v1/categories/{{category_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
DELETE {{base_url}}/api/v1/categories/{{category_id}}
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Deleted",
  "data": null,
  "errors": null
}
```

---

### POST Toggle Category Status

**Endpoint:** `POST /api/v1/categories/{{category_id}}/toggle-status`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/categories/{{category_id}}/toggle-status
Accept: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
    "status": "active"
}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Status updated",
  "data": {
    "id": 1,
    "parent_id": null,
    "name": "Electronics",
    "code": "ELEC",
    "status": "inactive",
    "created_at": "2026-06-30T14:28:13.000000Z",
    "updated_at": "2026-06-30T15:00:46.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

## Items

### GET List Items

**Endpoint:** `GET /api/v1/items`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/items
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success - empty)

```json
{
  "success": true,
  "message": "Success",
  "data": [],
  "errors": null,
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 0
  },
  "links": {
    "first": "http://127.0.0.1:8000/api/v1/items?page=1",
    "last": "http://127.0.0.1:8000/api/v1/items?page=1",
    "prev": null,
    "next": null
  }
}
```

---

### POST Create Item

**Endpoint:** `POST /api/v1/items`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/items
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
  "name": "Widget A",
  "unit_of_measure_id": 1,
  "category_id": 1,
  "supplier_id": 1,
  "barcode": "1234567890",
  "item_type": "product",
  "brand": "BrandX",
  "description": "A sample widget",
  "unit_cost": 10.00,
  "selling_price": 15.00,
  "reorder_level": 10,
  "status": "active"
}
```

#### Response — `201 Created` (success)

```json
{
  "success": true,
  "message": "Item created",
  "data": {
    "category_id": 1,
    "unit_of_measure_id": 1,
    "supplier_id": 1,
    "barcode": "1234567890",
    "name": "Widget A",
    "item_type": "product",
    "brand": "BrandX",
    "description": "A sample widget",
    "unit_cost": 10,
    "selling_price": 15,
    "reorder_level": 10,
    "status": "active",
    "sku": "ITM-000001",
    "created_by": 2,
    "updated_at": "2026-06-30T19:51:14.000000Z",
    "created_at": "2026-06-30T19:51:14.000000Z",
    "id": 3,
    "category": {
      "id": 1,
      "parent_id": null,
      "name": "Electronics",
      "code": "ELEC",
      "status": "inactive",
      "created_at": "2026-06-30T14:28:13.000000Z",
      "updated_at": "2026-06-30T15:00:46.000000Z",
      "deleted_at": null
    },
    "unit": {
      "id": 1,
      "name": "Kilogram",
      "abbreviation": "kg",
      "status": "inactive",
      "created_at": "2026-06-30T16:53:31.000000Z",
      "updated_at": "2026-06-30T17:56:32.000000Z",
      "deleted_at": null
    },
    "supplier": {
      "id": 1,
      "name": "Acme Corp Updated",
      "code": "ACME",
      "phone": "+1234567890",
      "email": "contact@acme.com",
      "address": "456 Supplier Ave",
      "status": "inactive",
      "created_at": "2026-06-30T17:57:35.000000Z",
      "updated_at": "2026-06-30T18:05:57.000000Z",
      "deleted_at": null
    }
  },
  "errors": null
}
```

#### Response — `422 Unprocessable Content` (validation failed)

```json
{
  "success": false,
  "message": "Validation failed.",
  "data": null,
  "errors": {
    "unit_of_measure_id": [
      "The selected unit of measure id is invalid."
    ],
    "supplier_id": [
      "The selected supplier id is invalid."
    ]
  }
}
```

---

### GET Get Item

**Endpoint:** `GET /api/v1/items/{{item_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/items/{{item_id}}
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": 3,
    "category_id": 1,
    "unit_of_measure_id": 1,
    "supplier_id": 1,
    "sku": "ITM-000001",
    "barcode": "1234567890",
    "name": "Widget A",
    "item_type": "product",
    "brand": "BrandX",
    "description": "A sample widget",
    "unit_cost": "10.00",
    "selling_price": "15.00",
    "reorder_level": "10.000",
    "image_path": null,
    "status": "active",
    "created_by": 2,
    "created_at": "2026-06-30T19:51:14.000000Z",
    "updated_at": "2026-06-30T19:51:14.000000Z",
    "deleted_at": null,
    "category": {
      "id": 1,
      "parent_id": null,
      "name": "Electronics",
      "code": "ELEC",
      "status": "inactive"
    },
    "unit": {
      "id": 1,
      "name": "Kilogram",
      "abbreviation": "kg",
      "status": "inactive"
    },
    "supplier": {
      "id": 1,
      "name": "Acme Corp Updated",
      "code": "ACME",
      "phone": "+1234567890",
      "email": "contact@acme.com",
      "address": "456 Supplier Ave",
      "status": "inactive"
    },
    "stock_balances": []
  },
  "errors": null
}
```

---

### PUT Update Item

**Endpoint:** `PUT /api/v1/items/{{item_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
PUT {{base_url}}/api/v1/items/{{item_id}}
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
  "name": "Widget A Updated",
  "unit_cost": 12.00,
  "selling_price": 18.00,
  "reorder_level": 15
}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Item updated",
  "data": {
    "id": 3,
    "category_id": 1,
    "unit_of_measure_id": 1,
    "supplier_id": 1,
    "sku": "ITM-000001",
    "barcode": "1234567890",
    "name": "Widget A Updated",
    "item_type": "product",
    "brand": "BrandX",
    "description": "A sample widget",
    "unit_cost": "12.00",
    "selling_price": "18.00",
    "reorder_level": "15.000",
    "image_path": null,
    "status": "active",
    "created_by": 2,
    "created_at": "2026-06-30T19:51:14.000000Z",
    "updated_at": "2026-06-30T19:53:07.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

### DELETE Delete Item

**Endpoint:** `DELETE /api/v1/items/{{item_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
DELETE {{base_url}}/api/v1/items/{{item_id}}
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Item deleted",
  "data": null,
  "errors": null
}
```

---

### POST Toggle Item Status

**Endpoint:** `POST /api/v1/items/{{item_id}}/toggle-status`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/items/{{item_id}}/toggle-status
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Status updated",
  "data": {
    "id": 3,
    "category_id": 1,
    "unit_of_measure_id": 1,
    "supplier_id": 1,
    "sku": "ITM-000001",
    "barcode": "1234567890",
    "name": "Widget A Updated",
    "item_type": "product",
    "brand": "BrandX",
    "description": "A sample widget",
    "unit_cost": "12.00",
    "selling_price": "18.00",
    "reorder_level": "15.000",
    "image_path": null,
    "status": "inactive",
    "created_by": 2,
    "created_at": "2026-06-30T19:51:14.000000Z",
    "updated_at": "2026-06-30T20:25:18.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

### GET Get Item Stock Balance

**Endpoint:** `GET /api/v1/items/{{item_id}}/stock-balance`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/items/{{item_id}}/stock-balance
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "total_on_hand": 0,
    "total_reserved": 0,
    "total_available": 0,
    "by_location": []
  },
  "errors": null
}
```

---

### GET Get Item Transactions

**Endpoint:** `GET /api/v1/items/{{item_id}}/transactions`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/items/{{item_id}}/transactions
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success - empty)

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "current_page": 1,
    "data": [],
    "first_page_url": "http://127.0.0.1:8000/api/v1/items/3/transactions?page=1",
    "from": null,
    "last_page": 1,
    "last_page_url": "http://127.0.0.1:8000/api/v1/items/3/transactions?page=1",
    "links": [
      { "url": null, "label": "&laquo; Previous", "page": null, "active": false },
      { "url": "http://127.0.0.1:8000/api/v1/items/3/transactions?page=1", "label": "1", "page": 1, "active": true },
      { "url": null, "label": "Next &raquo;", "page": null, "active": false }
    ],
    "next_page_url": null,
    "path": "http://127.0.0.1:8000/api/v1/items/3/transactions",
    "per_page": 20,
    "prev_page_url": null,
    "to": null,
    "total": 0
  },
  "errors": null
}
```

---

### POST Upload Item Image

**Endpoint:** `POST /api/v1/items/{{item_id}}/image`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/items/{{item_id}}/image
Accept: application/json
Authorization: Bearer {{access_token}}
Content-Type: multipart/form-data
```

**Body:** `form-data`

| Key | Type | Value |
|-----|------|-------|
| `image` | file | _(image file)_ |

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Image uploaded",
  "data": {
    "id": 3,
    "category_id": 1,
    "unit_of_measure_id": 1,
    "supplier_id": 1,
    "sku": "ITM-000001",
    "barcode": "1234567890",
    "name": "Widget A Updated",
    "item_type": "product",
    "brand": "BrandX",
    "description": "A sample widget",
    "unit_cost": "12.00",
    "selling_price": "18.00",
    "reorder_level": "15.000",
    "image_path": "items/3/Aev8KuDaAb8INF5bUorUp2MkzibeBbF0LR7xtcxB.jpg",
    "status": "active",
    "created_by": 2,
    "created_at": "2026-06-30T19:51:14.000000Z",
    "updated_at": "2026-06-30T20:03:51.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

#### Response — `422 Unprocessable Content` (wrong file extension)

```json
{
  "success": false,
  "message": "Validation failed.",
  "data": null,
  "errors": {
    "image": [
      "The image field must be an image."
    ]
  }
}
```

---

### DELETE Remove Item Image

**Endpoint:** `DELETE /api/v1/items/{{item_id}}/image`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
DELETE {{base_url}}/api/v1/items/{{item_id}}/image
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Image removed",
  "data": {
    "id": 3,
    "category_id": 1,
    "unit_of_measure_id": 1,
    "supplier_id": 1,
    "sku": "ITM-000001",
    "barcode": "1234567890",
    "name": "Widget A Updated",
    "item_type": "product",
    "brand": "BrandX",
    "description": "A sample widget",
    "unit_cost": "12.00",
    "selling_price": "18.00",
    "reorder_level": "15.000",
    "image_path": null,
    "status": "active",
    "created_by": 2,
    "created_at": "2026-06-30T19:51:14.000000Z",
    "updated_at": "2026-06-30T20:11:47.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

## Roles & Permissions

### GET List Roles

**Endpoint:** `GET /api/v1/roles`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/roles
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": 1,
      "name": "system_administrator",
      "guard_name": "api",
      "created_at": "2026-06-30T03:38:09.000000Z",
      "updated_at": "2026-06-30T03:38:09.000000Z",
      "permissions": [
        { "id": 1, "name": "items.view", "guard_name": "api" },
        { "id": 2, "name": "items.create", "guard_name": "api" },
        { "id": 3, "name": "items.edit", "guard_name": "api" },
        { "id": 4, "name": "items.delete", "guard_name": "api" },
        { "id": 5, "name": "receipts.view", "guard_name": "api" },
        { "id": 6, "name": "receipts.create", "guard_name": "api" },
        { "id": 7, "name": "receipts.approve", "guard_name": "api" },
        { "id": 8, "name": "transfers.view", "guard_name": "api" },
        { "id": 9, "name": "transfers.create", "guard_name": "api" },
        { "id": 10, "name": "transfers.approve", "guard_name": "api" },
        { "id": 11, "name": "adjustments.view", "guard_name": "api" },
        { "id": 12, "name": "adjustments.create", "guard_name": "api" },
        { "id": 13, "name": "adjustments.approve", "guard_name": "api" },
        { "id": 14, "name": "stock_counts.view", "guard_name": "api" },
        { "id": 15, "name": "stock_counts.create", "guard_name": "api" },
        { "id": 16, "name": "stock_counts.approve", "guard_name": "api" },
        { "id": 17, "name": "reports.view", "guard_name": "api" },
        { "id": 18, "name": "data_import.manage", "guard_name": "api" },
        { "id": 19, "name": "audit_logs.view", "guard_name": "api" },
        { "id": 20, "name": "users.manage", "guard_name": "api" },
        { "id": 21, "name": "settings.manage", "guard_name": "api" },
        { "id": 22, "name": "master-data.manage", "guard_name": "api" }
      ]
    },
    {
      "id": 2,
      "name": "warehouse_manager",
      "guard_name": "api",
      "created_at": "2026-06-30T03:38:09.000000Z",
      "updated_at": "2026-06-30T03:38:09.000000Z",
      "permissions": [
        { "id": 1, "name": "items.view", "guard_name": "api" },
        { "id": 2, "name": "items.create", "guard_name": "api" },
        { "id": 3, "name": "items.edit", "guard_name": "api" },
        { "id": 5, "name": "receipts.view", "guard_name": "api" },
        { "id": 6, "name": "receipts.create", "guard_name": "api" },
        { "id": 7, "name": "receipts.approve", "guard_name": "api" },
        { "id": 8, "name": "transfers.view", "guard_name": "api" },
        { "id": 9, "name": "transfers.create", "guard_name": "api" },
        { "id": 10, "name": "transfers.approve", "guard_name": "api" },
        { "id": 11, "name": "adjustments.view", "guard_name": "api" },
        { "id": 12, "name": "adjustments.create", "guard_name": "api" },
        { "id": 13, "name": "adjustments.approve", "guard_name": "api" },
        { "id": 14, "name": "stock_counts.view", "guard_name": "api" },
        { "id": 15, "name": "stock_counts.create", "guard_name": "api" },
        { "id": 16, "name": "stock_counts.approve", "guard_name": "api" },
        { "id": 17, "name": "reports.view", "guard_name": "api" },
        { "id": 18, "name": "data_import.manage", "guard_name": "api" },
        { "id": 22, "name": "master-data.manage", "guard_name": "api" }
      ]
    }
  ],
  "errors": null
}
```

---

### GET Get Role

**Endpoint:** `GET /api/v1/roles/{{role_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/roles/{{role_id}}
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": 2,
    "name": "warehouse_manager",
    "guard_name": "api",
    "created_at": "2026-06-30T03:38:09.000000Z",
    "updated_at": "2026-06-30T03:38:09.000000Z",
    "permissions": [
      { "id": 1, "name": "items.view", "guard_name": "api" },
      { "id": 2, "name": "items.create", "guard_name": "api" },
      { "id": 3, "name": "items.edit", "guard_name": "api" },
      { "id": 5, "name": "receipts.view", "guard_name": "api" },
      { "id": 6, "name": "receipts.create", "guard_name": "api" },
      { "id": 7, "name": "receipts.approve", "guard_name": "api" },
      { "id": 8, "name": "transfers.view", "guard_name": "api" },
      { "id": 9, "name": "transfers.create", "guard_name": "api" },
      { "id": 10, "name": "transfers.approve", "guard_name": "api" },
      { "id": 11, "name": "adjustments.view", "guard_name": "api" },
      { "id": 12, "name": "adjustments.create", "guard_name": "api" },
      { "id": 13, "name": "adjustments.approve", "guard_name": "api" },
      { "id": 14, "name": "stock_counts.view", "guard_name": "api" },
      { "id": 15, "name": "stock_counts.create", "guard_name": "api" },
      { "id": 16, "name": "stock_counts.approve", "guard_name": "api" },
      { "id": 17, "name": "reports.view", "guard_name": "api" },
      { "id": 18, "name": "data_import.manage", "guard_name": "api" },
      { "id": 22, "name": "master-data.manage", "guard_name": "api" }
    ]
  },
  "errors": null
}
```

---

### GET List Permissions

**Endpoint:** `GET /api/v1/permissions`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/permissions
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Success",
  "data": [
    { "id": 13, "name": "adjustments.approve", "guard_name": "api" },
    { "id": 12, "name": "adjustments.create", "guard_name": "api" },
    { "id": 11, "name": "adjustments.view", "guard_name": "api" },
    { "id": 19, "name": "audit_logs.view", "guard_name": "api" },
    { "id": 18, "name": "data_import.manage", "guard_name": "api" },
    { "id": 2, "name": "items.create", "guard_name": "api" },
    { "id": 4, "name": "items.delete", "guard_name": "api" },
    { "id": 3, "name": "items.edit", "guard_name": "api" },
    { "id": 1, "name": "items.view", "guard_name": "api" },
    { "id": 22, "name": "master-data.manage", "guard_name": "api" },
    { "id": 7, "name": "receipts.approve", "guard_name": "api" },
    { "id": 6, "name": "receipts.create", "guard_name": "api" },
    { "id": 5, "name": "receipts.view", "guard_name": "api" },
    { "id": 17, "name": "reports.view", "guard_name": "api" },
    { "id": 21, "name": "settings.manage", "guard_name": "api" },
    { "id": 16, "name": "stock_counts.approve", "guard_name": "api" },
    { "id": 15, "name": "stock_counts.create", "guard_name": "api" },
    { "id": 14, "name": "stock_counts.view", "guard_name": "api" },
    { "id": 10, "name": "transfers.approve", "guard_name": "api" },
    { "id": 9, "name": "transfers.create", "guard_name": "api" },
    { "id": 8, "name": "transfers.view", "guard_name": "api" },
    { "id": 20, "name": "users.manage", "guard_name": "api" }
  ],
  "errors": null
}
```

---

## Suppliers

### GET List Suppliers

**Endpoint:** `GET /api/v1/suppliers`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/suppliers
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Success",
  "data": [],
  "errors": null,
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 0
  },
  "links": {
    "first": "http://127.0.0.1:8000/api/v1/suppliers?page=1",
    "last": "http://127.0.0.1:8000/api/v1/suppliers?page=1",
    "prev": null,
    "next": null
  }
}
```

---

### POST Create Supplier

**Endpoint:** `POST /api/v1/suppliers`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/suppliers
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
  "name": "Acme Corp",
  "code": "ACME",
  "email": "contact@acme.com",
  "phone": "+1234567890",
  "address": "456 Supplier Ave",
  "status": "active"
}
```

#### Response — `201 Created` (success)

```json
{
  "success": true,
  "message": "Created successfully",
  "data": {
    "name": "Acme Corp",
    "code": "ACME",
    "email": "contact@acme.com",
    "phone": "+1234567890",
    "address": "456 Supplier Ave",
    "status": "active",
    "updated_at": "2026-06-30T17:57:35.000000Z",
    "created_at": "2026-06-30T17:57:35.000000Z",
    "id": 1
  },
  "errors": null
}
```

---

### GET Get Supplier

**Endpoint:** `GET /api/v1/suppliers/{{supplier_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/suppliers/{{supplier_id}}
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": 1,
    "name": "Acme Corp",
    "code": "ACME",
    "phone": "+1234567890",
    "email": "contact@acme.com",
    "address": "456 Supplier Ave",
    "status": "active",
    "created_at": "2026-06-30T17:57:35.000000Z",
    "updated_at": "2026-06-30T17:57:35.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

### PUT Update Supplier

**Endpoint:** `PUT /api/v1/suppliers/{{supplier_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
PUT {{base_url}}/api/v1/suppliers/{{supplier_id}}
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
  "name": "Acme Corp Updated",
  "code": "ACME",
  "email": "contact@acme.com",
  "phone": "+1234567890",
  "address": "456 Supplier Ave",
  "status": "inactive"
}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Updated",
  "data": {
    "id": 1,
    "name": "Acme Corp Updated",
    "code": "ACME",
    "phone": "+1234567890",
    "email": "contact@acme.com",
    "address": "456 Supplier Ave",
    "status": "inactive",
    "created_at": "2026-06-30T17:57:35.000000Z",
    "updated_at": "2026-06-30T17:59:05.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

### DELETE Delete Supplier

**Endpoint:** `DELETE /api/v1/suppliers/{{supplier_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
DELETE {{base_url}}/api/v1/suppliers/{{supplier_id}}
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Deleted",
  "data": null,
  "errors": null
}
```

---

### POST Toggle Supplier Status

**Endpoint:** `POST /api/v1/suppliers/{{supplier_id}}/toggle-status`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/suppliers/{{supplier_id}}/toggle-status
Accept: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
    "status": "inactive"
}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Status updated",
  "data": {
    "id": 1,
    "name": "Acme Corp Updated",
    "code": "ACME",
    "phone": "+1234567890",
    "email": "contact@acme.com",
    "address": "456 Supplier Ave",
    "status": "inactive",
    "created_at": "2026-06-30T17:57:35.000000Z",
    "updated_at": "2026-06-30T18:05:57.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

#### Response — `404 Not Found` (supplier does not exist)

```json
{
  "success": false,
  "message": "No query results for model [App\\Models\\Supplier] 1",
  "data": null,
  "errors": null
}
```

---

## Units of Measure

### GET List Units

**Endpoint:** `GET /api/v1/units`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/units
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": 1,
      "name": "Kilogram",
      "abbreviation": "kg",
      "status": "active",
      "created_at": "2026-06-30T16:53:31.000000Z",
      "updated_at": "2026-06-30T16:53:31.000000Z",
      "deleted_at": null
    }
  ],
  "errors": null,
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  },
  "links": {
    "first": "http://127.0.0.1:8000/api/v1/units?page=1",
    "last": "http://127.0.0.1:8000/api/v1/units?page=1",
    "prev": null,
    "next": null
  }
}
```

---

### POST Create Unit

**Endpoint:** `POST /api/v1/units`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/units
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
  "name": "Kilogram",
  "abbreviation": "kg",
  "status": "active"
}
```

#### Response — `201 Created` (success)

```json
{
  "success": true,
  "message": "Created successfully",
  "data": {
    "name": "Kilogram",
    "abbreviation": "kg",
    "status": "active",
    "updated_at": "2026-06-30T16:53:31.000000Z",
    "created_at": "2026-06-30T16:53:31.000000Z",
    "id": 1
  },
  "errors": null
}
```

---

### GET Get Unit

**Endpoint:** `GET /api/v1/units/{{unit_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/units/{{unit_id}}
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": 1,
    "name": "Kilogram",
    "abbreviation": "kg",
    "status": "active",
    "created_at": "2026-06-30T16:53:31.000000Z",
    "updated_at": "2026-06-30T16:53:31.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

### PUT Update Unit

**Endpoint:** `PUT /api/v1/units/{{unit_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
PUT {{base_url}}/api/v1/units/{{unit_id}}
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
  "name": "Kilogram",
  "abbreviation": "kg",
  "status": "inactive"
}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Updated",
  "data": {
    "id": 1,
    "name": "Kilogram",
    "abbreviation": "kg",
    "status": "inactive",
    "created_at": "2026-06-30T16:53:31.000000Z",
    "updated_at": "2026-06-30T17:55:57.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

### DELETE Delete Unit

**Endpoint:** `DELETE /api/v1/units/{{unit_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
DELETE {{base_url}}/api/v1/units/{{unit_id}}
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Deleted",
  "data": null,
  "errors": null
}
```

---

### POST Toggle Unit Status

**Endpoint:** `POST /api/v1/units/{{unit_id}}/toggle-status`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/units/{{unit_id}}/toggle-status
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Status updated",
  "data": {
    "id": 1,
    "name": "Kilogram",
    "abbreviation": "kg",
    "status": "active",
    "created_at": "2026-06-30T16:53:31.000000Z",
    "updated_at": "2026-06-30T20:35:28.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

## Users

### GET List Users

**Endpoint:** `GET /api/v1/users`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/users
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": 2,
      "name": "System Administrator",
      "email": "admin@inventory.local",
      "phone": null,
      "status": "active",
      "email_verified_at": null,
      "created_at": "2026-06-30T03:38:09.000000Z",
      "updated_at": "2026-06-30T03:38:09.000000Z",
      "deleted_at": null,
      "roles": [
        {
          "id": 1,
          "name": "system_administrator",
          "guard_name": "api",
          "created_at": "2026-06-30T03:38:09.000000Z",
          "updated_at": "2026-06-30T03:38:09.000000Z",
          "pivot": {
            "model_type": "App\\Models\\User",
            "model_id": 2,
            "role_id": 1
          }
        }
      ]
    },
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": null,
      "status": "active",
      "email_verified_at": null,
      "created_at": "2026-06-30T03:12:26.000000Z",
      "updated_at": "2026-06-30T03:12:26.000000Z",
      "deleted_at": null,
      "roles": []
    }
  ],
  "errors": null,
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 2
  },
  "links": {
    "first": "http://127.0.0.1:8000/api/v1/users?page=1",
    "last": "http://127.0.0.1:8000/api/v1/users?page=1",
    "prev": null,
    "next": null
  }
}
```

---

### POST Create User

**Endpoint:** `POST /api/v1/users`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/users
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
  "name": "Jackson Bello",
  "email": "jcko@example.com",
  "phone": "+1234567890",
  "password": "password123",
  "status": "active",
  "roles": ["management_viewer"]
}
```

#### Response — `201 Created` (success)

```json
{
  "success": true,
  "message": "User created",
  "data": {
    "name": "Jackson Bello",
    "email": "jcko@example.com",
    "phone": "+1234567890",
    "status": "active",
    "updated_at": "2026-06-30T16:40:59.000000Z",
    "created_at": "2026-06-30T16:40:59.000000Z",
    "id": 4,
    "roles": [
      {
        "id": 4,
        "name": "management_viewer",
        "guard_name": "api",
        "created_at": "2026-06-30T03:38:09.000000Z",
        "updated_at": "2026-06-30T03:38:09.000000Z",
        "pivot": {
          "model_type": "App\\Models\\User",
          "model_id": 4,
          "role_id": 4
        }
      }
    ]
  },
  "errors": null
}
```

---

### GET Get User

**Endpoint:** `GET /api/v1/users/{{user_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
GET {{base_url}}/api/v1/users/{{user_id}}
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": null,
    "status": "active",
    "email_verified_at": null,
    "created_at": "2026-06-30T03:12:26.000000Z",
    "updated_at": "2026-06-30T03:12:26.000000Z",
    "deleted_at": null,
    "roles": []
  },
  "errors": null
}
```

---

### PUT Update User

**Endpoint:** `PUT /api/v1/users/{{user_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
PUT {{base_url}}/api/v1/users/{{user_id}}
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
  "name": "John Doe Updated",
  "email": "john@example.com",
  "phone": "+1234567890",
  "status": "active"
}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "User updated",
  "data": {
    "id": 1,
    "name": "John Doe Updated",
    "email": "john@example.com",
    "phone": "+1234567890",
    "status": "active",
    "email_verified_at": null,
    "created_at": "2026-06-30T03:12:26.000000Z",
    "updated_at": "2026-06-30T13:29:51.000000Z",
    "deleted_at": null,
    "roles": []
  },
  "errors": null
}
```

---

### DELETE Delete User

**Endpoint:** `DELETE /api/v1/users/{{user_id}}`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
DELETE {{base_url}}/api/v1/users/{{user_id}}
Accept: application/json
Authorization: Bearer {{access_token}}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "User deleted",
  "data": null,
  "errors": null
}
```

---

### POST Toggle User Status

**Endpoint:** `POST /api/v1/users/{{user_id}}/toggle-status`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/users/{{user_id}}/toggle-status
Accept: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
  "status": "inactive"
}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "User status updated",
  "data": {
    "id": 2,
    "name": "System Administrator",
    "email": "admin@inventory.local",
    "phone": null,
    "status": "inactive",
    "email_verified_at": null,
    "created_at": "2026-06-30T03:38:09.000000Z",
    "updated_at": "2026-06-30T14:13:30.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

#### Response — `404 Not Found` (user does not exist)

```json
{
  "success": false,
  "message": "No query results for model [App\\Models\\User] 1",
  "data": null,
  "errors": null
}
```

---

### POST Assign Role

**Endpoint:** `POST /api/v1/users/{{user_id}}/assign-role`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/users/{{user_id}}/assign-role
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
  "roles": ["inventory_officer"]
}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Role assigned",
  "data": {
    "id": 1,
    "name": "John Doe Updated",
    "email": "john@example.com",
    "phone": "+1234567890",
    "status": "active",
    "email_verified_at": null,
    "created_at": "2026-06-30T03:12:26.000000Z",
    "updated_at": "2026-06-30T13:29:51.000000Z",
    "deleted_at": null,
    "roles": [
      {
        "id": 3,
        "name": "inventory_officer",
        "guard_name": "api",
        "created_at": "2026-06-30T03:38:09.000000Z",
        "updated_at": "2026-06-30T03:38:09.000000Z",
        "pivot": {
          "model_type": "App\\Models\\User",
          "model_id": 1,
          "role_id": 3
        }
      }
    ]
  },
  "errors": null
}
```

---

### POST Remove Role

**Endpoint:** `POST /api/v1/users/{{user_id}}/remove-role`  
**Auth:** Bearer `{{access_token}}`

#### Request

```http
POST {{base_url}}/api/v1/users/{{user_id}}/remove-role
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{access_token}}
```

**Body:**
```json
{
  "role": "inventory_officer"
}
```

#### Response — `200 OK` (success)

```json
{
  "success": true,
  "message": "Role removed",
  "data": {
    "id": 1,
    "name": "John Doe Updated",
    "email": "john@example.com",
    "phone": "+1234567890",
    "status": "active",
    "email_verified_at": null,
    "created_at": "2026-06-30T03:12:26.000000Z",
    "updated_at": "2026-06-30T13:46:14.000000Z",
    "deleted_at": null,
    "roles": []
  },
  "errors": null
}
```

---

*Generated from `postman/collections/Entouche API/` — 43 endpoints across 7 groups, 49 total recorded examples.*
