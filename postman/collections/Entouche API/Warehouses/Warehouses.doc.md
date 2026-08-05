# Warehouses API

## Overview

The Warehouses API group provides endpoints for managing warehouse resources within the Entouche platform. It covers the full lifecycle of a warehouse — from creation and retrieval to updates, deletion, and status management. Additional endpoints allow you to inspect warehouse locations and view stock summaries, making this group the central hub for warehouse operations.

All endpoints require Bearer token authentication via `{{access_token}}`.

---

## Endpoint Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/warehouses` | List all warehouses |
| `POST` | `/api/v1/warehouses` | Create a new warehouse |
| `GET` | `/api/v1/warehouses/{{warehouse_id}}` | Get a single warehouse by ID |
| `PUT` | `/api/v1/warehouses/{{warehouse_id}}` | Update an existing warehouse |
| `DELETE` | `/api/v1/warehouses/{{warehouse_id}}` | Delete a warehouse |
| `POST` | `/api/v1/warehouses/{{warehouse_id}}/toggle-status` | Toggle the active/inactive status of a warehouse |
| `GET` | `/api/v1/warehouses/{{warehouse_id}}/locations` | Get all locations within a warehouse |
| `GET` | `/api/v1/warehouses/{{warehouse_id}}/stock-summary` | Get the stock summary for a warehouse |

---

## Endpoints

---

### 1. List Warehouses

> **`GET`** `{{base_url}}/api/v1/warehouses`

Retrieves a list of all warehouses available in the system.

**Authentication:** Bearer `{{access_token}}`

**Headers:**

| Key | Value |
|-----|-------|
| `Accept` | `application/json` |

**Request Body:** None

---

### 2. Create Warehouse

> **`POST`** `{{base_url}}/api/v1/warehouses`

Creates a new warehouse with the provided details.

**Authentication:** Bearer `{{access_token}}`

**Headers:**

| Key | Value |
|-----|-------|
| `Accept` | `application/json` |
| `Content-Type` | `application/json` |

**Request Body (JSON):**

```json
{
  "name": "Secondary Warehouse",
  "code": "WH-002",
  "address": "123 Main St",
  "status": "active"
}
```

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Human-readable name of the warehouse |
| `code` | string | Unique short code identifier for the warehouse |
| `address` | string | Physical address of the warehouse |
| `status` | string | Initial status — `active` or `inactive` |

---

### 3. Get Warehouse

> **`GET`** `{{base_url}}/api/v1/warehouses/{{warehouse_id}}`

Retrieves the details of a specific warehouse by its ID.

**Authentication:** Bearer `{{access_token}}`

**Headers:**

| Key | Value |
|-----|-------|
| `Accept` | `application/json` |

**Path Variables:**

| Variable | Description |
|----------|-------------|
| `warehouse_id` | The unique identifier of the warehouse to retrieve |

**Request Body:** None

---

### 4. Update Warehouse

> **`PUT`** `{{base_url}}/api/v1/warehouses/{{warehouse_id}}`

Updates the details of an existing warehouse.

**Authentication:** Bearer `{{access_token}}`

**Headers:**

| Key | Value |
|-----|-------|
| `Accept` | `application/json` |
| `Content-Type` | `application/json` |

**Path Variables:**

| Variable | Description |
|----------|-------------|
| `warehouse_id` | The unique identifier of the warehouse to update |

**Request Body (JSON):**

```json
{
  "name": "Main Warehouse",
  "code": "WH-001",
  "address": "123 Main St",
  "status": "active"
}
```

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Updated name of the warehouse |
| `code` | string | Updated short code identifier |
| `address` | string | Updated physical address |
| `status` | string | Updated status — `active` or `inactive` |

---

### 5. Delete Warehouse

> **`DELETE`** `{{base_url}}/api/v1/warehouses/{{warehouse_id}}`

Permanently deletes a warehouse by its ID.

**Authentication:** Bearer `{{access_token}}`

**Headers:**

| Key | Value |
|-----|-------|
| `Accept` | `application/json` |

**Path Variables:**

| Variable | Description |
|----------|-------------|
| `warehouse_id` | The unique identifier of the warehouse to delete |

**Request Body:** None

---

### 6. Toggle Warehouse Status

> **`POST`** `{{base_url}}/api/v1/warehouses/{{warehouse_id}}/toggle-status`

Toggles the active/inactive status of a warehouse. If the warehouse is currently `active`, it will be set to `inactive`, and vice versa.

**Authentication:** Bearer `{{access_token}}`

**Headers:**

| Key | Value |
|-----|-------|
| `Accept` | `application/json` |

**Path Variables:**

| Variable | Description |
|----------|-------------|
| `warehouse_id` | The unique identifier of the warehouse whose status will be toggled |

**Request Body:** None

---

### 7. Get Warehouse Locations

> **`GET`** `{{base_url}}/api/v1/warehouses/{{warehouse_id}}/locations`

Retrieves all storage locations defined within a specific warehouse.

**Authentication:** Bearer `{{access_token}}`

**Headers:**

| Key | Value |
|-----|-------|
| `Accept` | `application/json` |

**Path Variables:**

| Variable | Description |
|----------|-------------|
| `warehouse_id` | The unique identifier of the warehouse whose locations are being retrieved |

**Request Body:** None

---

### 8. Get Warehouse Stock Summary

> **`GET`** `{{base_url}}/api/v1/warehouses/{{warehouse_id}}/stock-summary`

Returns a summary of the current stock levels held within a specific warehouse.

**Authentication:** Bearer `{{access_token}}`

**Headers:**

| Key | Value |
|-----|-------|
| `Accept` | `application/json` |

**Path Variables:**

| Variable | Description |
|----------|-------------|
| `warehouse_id` | The unique identifier of the warehouse whose stock summary is being retrieved |

**Request Body:** None

---

## Variables

The following Postman variables are used across all endpoints in this group. Ensure they are defined in your active environment or collection variables before sending requests.

| Variable | Description |
|----------|-------------|
| `{{base_url}}` | The base URL of the Entouche API (e.g. `https://api.entouche.com`) |
| `{{access_token}}` | A valid Bearer token used to authenticate all requests |
| `{{warehouse_id}}` | The ID of the target warehouse, used in single-resource endpoints |
