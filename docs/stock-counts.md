# Stock Counts API

Stock Counts allow you to record and reconcile physical inventory counts against system quantities. A stock count follows a lifecycle: **draft → in_progress → completed → approved** (or **cancelled** at any stage).

All endpoints require Bearer token authentication via the `Authorization` header.

**Base URL:** `{{base_url}}/api/v1`

---

## Table of Contents

1. [List Stock Counts](#1-list-stock-counts)
2. [Create Stock Count](#2-create-stock-count)
3. [Get Stock Count](#3-get-stock-count)
4. [Update Stock Count](#4-update-stock-count)
5. [Delete Stock Count](#5-delete-stock-count)
6. [Start Stock Count](#6-start-stock-count)
7. [Complete Stock Count](#7-complete-stock-count)
8. [Approve Stock Count](#8-approve-stock-count)
9. [Cancel Stock Count](#9-cancel-stock-count)

---

## Status Lifecycle

```
draft → in_progress → completed → approved
                ↘                ↗
                      cancelled
```

| Status        | Description                                      |
|---------------|--------------------------------------------------|
| `draft`       | Newly created, not yet started                   |
| `in_progress` | Counting has been started                        |
| `completed`   | Counting is finished, pending approval           |
| `approved`    | Approved; inventory adjustments have been posted |
| `cancelled`   | Count was cancelled                              |

---

## Authentication

All requests must include a Bearer token:

```
Authorization: Bearer {{access_token}}
```

---

## 1. List Stock Counts

Retrieve a paginated list of all stock counts.

**`GET /api/v1/stock-counts`**

### Request

| Component | Details                    |
|-----------|----------------------------|
| Method    | `GET`                      |
| URL       | `{{base_url}}/api/v1/stock-counts` |
| Auth      | Bearer `{{access_token}}`  |

**Headers**

| Key      | Value              |
|----------|--------------------|
| `Accept` | `application/json` |

### Response

#### 200 OK — Success (with data)

```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": 3,
      "count_number": "SC-000003",
      "warehouse_id": 1,
      "warehouse_location_id": null,
      "counted_by": 2,
      "approved_by": null,
      "created_by": 2,
      "count_date": "2026-06-29T00:00:00.000000Z",
      "started_at": null,
      "completed_at": null,
      "approved_at": null,
      "status": "draft",
      "notes": null,
      "rejection_reason": null,
      "created_at": "2026-07-09T16:55:11.000000Z",
      "updated_at": "2026-07-09T16:55:11.000000Z",
      "deleted_at": null,
      "items": [
        {
          "id": 3,
          "stock_count_id": 3,
          "item_id": 5,
          "warehouse_location_id": null,
          "system_quantity": "100.000",
          "counted_quantity": "98.000",
          "variance_quantity": "-2.000",
          "adjustment_created": 0,
          "remarks": null,
          "created_at": "2026-07-09T16:55:11.000000Z",
          "updated_at": "2026-07-09T16:55:11.000000Z",
          "item": {
            "id": 5,
            "sku": "ITM-000003",
            "name": "Widget A",
            "item_type": "product",
            "unit_cost": "10.00",
            "selling_price": "15.00",
            "reorder_level": "10.000",
            "status": "active"
          }
        }
      ],
      "warehouse": {
        "id": 1,
        "name": "Main Warehouse",
        "code": "MAIN",
        "address": "Head Office",
        "status": "active"
      },
      "location": null
    }
  ],
  "errors": null,
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 3
  },
  "links": {
    "first": "http://127.0.0.1:8000/api/v1/stock-counts?page=1",
    "last": "http://127.0.0.1:8000/api/v1/stock-counts?page=1",
    "prev": null,
    "next": null
  }
}
```

#### 200 OK — Success (empty list)

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
    "first": "http://127.0.0.1:8000/api/v1/stock-counts?page=1",
    "last": "http://127.0.0.1:8000/api/v1/stock-counts?page=1",
    "prev": null,
    "next": null
  }
}
```

---

## 2. Create Stock Count

Create a new stock count record in `draft` status.

**`POST /api/v1/stock-counts`**

### Request

| Component | Details                    |
|-----------|----------------------------|
| Method    | `POST`                     |
| URL       | `{{base_url}}/api/v1/stock-counts` |
| Auth      | Bearer `{{access_token}}`  |

**Headers**

| Key            | Value              |
|----------------|--------------------|
| `Accept`       | `application/json` |
| `Content-Type` | `application/json` |

**Body**

```json
{
  "warehouse_id": 1,
  "warehouse_location_id": null,
  "count_date": "2026-06-29",
  "items": [
    {
      "item_id": 5,
      "system_quantity": 100,
      "counted_quantity": 98
    }
  ]
}
```

**Body Parameters**

| Field                  | Type            | Required | Description                                          |
|------------------------|-----------------|----------|------------------------------------------------------|
| `warehouse_id`         | integer         | Yes      | ID of the warehouse being counted                    |
| `warehouse_location_id`| integer \| null | No       | Specific location within the warehouse (optional)    |
| `count_date`           | string (date)   | Yes      | Date of the physical count (`YYYY-MM-DD`)            |
| `items`                | array           | Yes      | List of items to count                               |
| `items[].item_id`      | integer         | Yes      | ID of the item                                       |
| `items[].system_quantity` | number       | Yes      | Quantity recorded in the system                      |
| `items[].counted_quantity`| number       | Yes      | Physically counted quantity                          |

### Response

#### 201 Created — Success

```json
{
  "success": true,
  "message": "Stock count created",
  "data": {
    "id": 3,
    "count_number": "SC-000003",
    "warehouse_id": 1,
    "warehouse_location_id": null,
    "count_date": "2026-06-29T00:00:00.000000Z",
    "counted_by": 2,
    "created_by": 2,
    "status": "draft",
    "created_at": "2026-07-09T16:55:11.000000Z",
    "updated_at": "2026-07-09T16:55:11.000000Z",
    "items": [
      {
        "id": 3,
        "stock_count_id": 3,
        "item_id": 5,
        "warehouse_location_id": null,
        "system_quantity": "100.000",
        "counted_quantity": "98.000",
        "variance_quantity": "-2.000",
        "adjustment_created": 0,
        "remarks": null,
        "created_at": "2026-07-09T16:55:11.000000Z",
        "updated_at": "2026-07-09T16:55:11.000000Z"
      }
    ]
  },
  "errors": null
}
```

---

## 3. Get Stock Count

Retrieve a single stock count by its ID.

**`GET /api/v1/stock-counts/{stock_count_id}`**

### Request

| Component | Details                                              |
|-----------|------------------------------------------------------|
| Method    | `GET`                                                |
| URL       | `{{base_url}}/api/v1/stock-counts/{{stock_count_id}}` |
| Auth      | Bearer `{{access_token}}`                            |

**Path Parameters**

| Parameter       | Type    | Description              |
|-----------------|---------|--------------------------|
| `stock_count_id`| integer | ID of the stock count    |

**Headers**

| Key      | Value              |
|----------|--------------------|
| `Accept` | `application/json` |

### Response

#### 200 OK — Success

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": 1,
    "count_number": "SC-000001",
    "warehouse_id": 1,
    "warehouse_location_id": null,
    "counted_by": 2,
    "approved_by": null,
    "created_by": 2,
    "count_date": "2026-06-29T00:00:00.000000Z",
    "started_at": null,
    "completed_at": null,
    "approved_at": null,
    "status": "draft",
    "notes": null,
    "rejection_reason": null,
    "created_at": "2026-07-09T16:53:32.000000Z",
    "updated_at": "2026-07-09T16:53:32.000000Z",
    "deleted_at": null,
    "items": [
      {
        "id": 1,
        "stock_count_id": 1,
        "item_id": 5,
        "warehouse_location_id": null,
        "system_quantity": "100.000",
        "counted_quantity": "98.000",
        "variance_quantity": "-2.000",
        "adjustment_created": 0,
        "remarks": null,
        "created_at": "2026-07-09T16:53:32.000000Z",
        "updated_at": "2026-07-09T16:53:32.000000Z",
        "item": {
          "id": 5,
          "category_id": 1,
          "unit_of_measure_id": 1,
          "supplier_id": 1,
          "sku": "ITM-000003",
          "barcode": null,
          "name": "Widget A",
          "item_type": "product",
          "brand": null,
          "description": null,
          "unit_cost": "10.00",
          "selling_price": "15.00",
          "reorder_level": "10.000",
          "image_path": null,
          "status": "active",
          "created_by": 2,
          "created_at": "2026-07-09T16:50:07.000000Z",
          "updated_at": "2026-07-09T16:50:07.000000Z",
          "deleted_at": null
        }
      }
    ]
  },
  "errors": null
}
```

---

## 4. Update Stock Count

Update an existing stock count. Only allowed when the count is in `draft` status.

**`PUT /api/v1/stock-counts/{stock_count_id}`**

### Request

| Component | Details                                              |
|-----------|------------------------------------------------------|
| Method    | `PUT`                                                |
| URL       | `{{base_url}}/api/v1/stock-counts/{{stock_count_id}}` |
| Auth      | Bearer `{{access_token}}`                            |

**Path Parameters**

| Parameter       | Type    | Description              |
|-----------------|---------|--------------------------|
| `stock_count_id`| integer | ID of the stock count    |

**Headers**

| Key            | Value              |
|----------------|--------------------|
| `Accept`       | `application/json` |
| `Content-Type` | `application/json` |

**Body**

```json
{
  "notes": "Updated notes",
  "count_date": "2026-06-29"
}
```

**Body Parameters**

| Field        | Type          | Required | Description                          |
|--------------|---------------|----------|--------------------------------------|
| `notes`      | string \| null| No       | Optional notes for the stock count   |
| `count_date` | string (date) | No       | Updated count date (`YYYY-MM-DD`)    |

### Response

#### 200 OK — Success

```json
{
  "success": true,
  "message": "Stock count updated",
  "data": {
    "id": 1,
    "count_number": "SC-000001",
    "warehouse_id": 1,
    "warehouse_location_id": null,
    "counted_by": 2,
    "approved_by": null,
    "created_by": 2,
    "count_date": "2026-06-29T00:00:00.000000Z",
    "started_at": null,
    "completed_at": null,
    "approved_at": null,
    "status": "draft",
    "notes": "Updated notes",
    "rejection_reason": null,
    "created_at": "2026-07-09T16:53:32.000000Z",
    "updated_at": "2026-07-09T16:57:43.000000Z",
    "deleted_at": null,
    "items": [
      {
        "id": 1,
        "stock_count_id": 1,
        "item_id": 5,
        "warehouse_location_id": null,
        "system_quantity": "100.000",
        "counted_quantity": "98.000",
        "variance_quantity": "-2.000",
        "adjustment_created": 0,
        "remarks": null,
        "created_at": "2026-07-09T16:53:32.000000Z",
        "updated_at": "2026-07-09T16:53:32.000000Z"
      }
    ]
  },
  "errors": null
}
```

---

## 5. Delete Stock Count

Permanently delete a stock count. Only allowed when the count is in `draft` status.

**`DELETE /api/v1/stock-counts/{stock_count_id}`**

### Request

| Component | Details                                              |
|-----------|------------------------------------------------------|
| Method    | `DELETE`                                             |
| URL       | `{{base_url}}/api/v1/stock-counts/{{stock_count_id}}` |
| Auth      | Bearer `{{access_token}}`                            |

**Path Parameters**

| Parameter       | Type    | Description              |
|-----------------|---------|--------------------------|
| `stock_count_id`| integer | ID of the stock count    |

**Headers**

| Key      | Value              |
|----------|--------------------|
| `Accept` | `application/json` |

### Response

#### 200 OK — Success

```json
{
  "success": true,
  "message": "Stock count deleted",
  "data": null,
  "errors": null
}
```

---

## 6. Start Stock Count

Transition a stock count from `draft` to `in_progress`. The system quantity for each item is snapshotted at this point.

**`POST /api/v1/stock-counts/{stock_count_id}/start`**

### Request

| Component | Details                                                    |
|-----------|------------------------------------------------------------|
| Method    | `POST`                                                     |
| URL       | `{{base_url}}/api/v1/stock-counts/{{stock_count_id}}/start` |
| Auth      | Bearer `{{access_token}}`                                  |

**Path Parameters**

| Parameter       | Type    | Description              |
|-----------------|---------|--------------------------|
| `stock_count_id`| integer | ID of the stock count    |

**Headers**

| Key      | Value              |
|----------|--------------------|
| `Accept` | `application/json` |

### Response

#### 200 OK — Success

```json
{
  "success": true,
  "message": "Stock count started",
  "data": {
    "id": 2,
    "count_number": "SC-000002",
    "warehouse_id": 1,
    "warehouse_location_id": null,
    "counted_by": 2,
    "approved_by": null,
    "created_by": 2,
    "count_date": "2026-06-29T00:00:00.000000Z",
    "started_at": "2026-07-09T16:59:24.000000Z",
    "completed_at": null,
    "approved_at": null,
    "status": "in_progress",
    "notes": null,
    "rejection_reason": null,
    "created_at": "2026-07-09T16:54:10.000000Z",
    "updated_at": "2026-07-09T16:59:24.000000Z",
    "deleted_at": null,
    "items": [
      {
        "id": 2,
        "stock_count_id": 2,
        "item_id": 5,
        "warehouse_location_id": null,
        "system_quantity": "0.000",
        "counted_quantity": "98.000",
        "variance_quantity": "98.000",
        "adjustment_created": 0,
        "remarks": null,
        "created_at": "2026-07-09T16:54:10.000000Z",
        "updated_at": "2026-07-09T16:59:24.000000Z"
      }
    ]
  },
  "errors": null
}
```

---

## 7. Complete Stock Count

Mark a stock count as `completed`. The count must be `in_progress` before it can be completed.

**`POST /api/v1/stock-counts/{stock_count_id}/complete`**

### Request

| Component | Details                                                       |
|-----------|---------------------------------------------------------------|
| Method    | `POST`                                                        |
| URL       | `{{base_url}}/api/v1/stock-counts/{{stock_count_id}}/complete` |
| Auth      | Bearer `{{access_token}}`                                     |

**Path Parameters**

| Parameter       | Type    | Description              |
|-----------------|---------|--------------------------|
| `stock_count_id`| integer | ID of the stock count    |

**Headers**

| Key      | Value              |
|----------|--------------------|
| `Accept` | `application/json` |

### Response

#### 200 OK — Success

```json
{
  "success": true,
  "message": "Stock count completed",
  "data": {
    "id": 2,
    "count_number": "SC-000002",
    "warehouse_id": 1,
    "warehouse_location_id": null,
    "counted_by": 2,
    "approved_by": null,
    "created_by": 2,
    "count_date": "2026-06-29T00:00:00.000000Z",
    "started_at": "2026-07-09T16:59:24.000000Z",
    "completed_at": "2026-07-09T17:00:43.000000Z",
    "approved_at": null,
    "status": "completed",
    "notes": null,
    "rejection_reason": null,
    "created_at": "2026-07-09T16:54:10.000000Z",
    "updated_at": "2026-07-09T17:00:43.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

## 8. Approve Stock Count

Approve a completed stock count. This posts inventory adjustments for all variance items (`adjustment_created` is set to `1`).

**`POST /api/v1/stock-counts/{stock_count_id}/approve`**

### Request

| Component | Details                                                      |
|-----------|--------------------------------------------------------------|
| Method    | `POST`                                                       |
| URL       | `{{base_url}}/api/v1/stock-counts/{{stock_count_id}}/approve` |
| Auth      | Bearer `{{access_token}}`                                    |

**Path Parameters**

| Parameter       | Type    | Description              |
|-----------------|---------|--------------------------|
| `stock_count_id`| integer | ID of the stock count    |

**Headers**

| Key      | Value              |
|----------|--------------------|
| `Accept` | `application/json` |

### Response

#### 200 OK — Success

```json
{
  "success": true,
  "message": "Stock count approved",
  "data": {
    "id": 2,
    "count_number": "SC-000002",
    "warehouse_id": 1,
    "warehouse_location_id": null,
    "counted_by": 2,
    "approved_by": 2,
    "created_by": 2,
    "count_date": "2026-06-29T00:00:00.000000Z",
    "started_at": "2026-07-09T16:59:24.000000Z",
    "completed_at": "2026-07-09T17:00:43.000000Z",
    "approved_at": "2026-07-09T17:02:07.000000Z",
    "status": "approved",
    "notes": null,
    "rejection_reason": null,
    "created_at": "2026-07-09T16:54:10.000000Z",
    "updated_at": "2026-07-09T17:02:07.000000Z",
    "deleted_at": null,
    "items": [
      {
        "id": 2,
        "stock_count_id": 2,
        "item_id": 5,
        "warehouse_location_id": null,
        "system_quantity": "0.000",
        "counted_quantity": "98.000",
        "variance_quantity": "98.000",
        "adjustment_created": 1,
        "remarks": null,
        "created_at": "2026-07-09T16:54:10.000000Z",
        "updated_at": "2026-07-09T17:02:07.000000Z",
        "item": {
          "id": 5,
          "sku": "ITM-000003",
          "name": "Widget A",
          "item_type": "product",
          "unit_cost": "10.00",
          "selling_price": "15.00",
          "reorder_level": "10.000",
          "status": "active"
        }
      }
    ],
    "warehouse": {
      "id": 1,
      "name": "Main Warehouse",
      "code": "MAIN",
      "address": "Head Office",
      "status": "active"
    },
    "location": null
  },
  "errors": null
}
```

---

## 9. Cancel Stock Count

Cancel a stock count. Can be applied at any stage of the lifecycle.

**`POST /api/v1/stock-counts/{stock_count_id}/cancel`**

### Request

| Component | Details                                                     |
|-----------|-------------------------------------------------------------|
| Method    | `POST`                                                      |
| URL       | `{{base_url}}/api/v1/stock-counts/{{stock_count_id}}/cancel` |
| Auth      | Bearer `{{access_token}}`                                   |

**Path Parameters**

| Parameter       | Type    | Description              |
|-----------------|---------|--------------------------|
| `stock_count_id`| integer | ID of the stock count    |

**Headers**

| Key      | Value              |
|----------|--------------------|
| `Accept` | `application/json` |

### Response

#### 200 OK — Success

```json
{
  "success": true,
  "message": "Stock count cancelled",
  "data": {
    "id": 2,
    "count_number": "SC-000002",
    "warehouse_id": 1,
    "warehouse_location_id": null,
    "counted_by": 2,
    "approved_by": 2,
    "created_by": 2,
    "count_date": "2026-06-29T00:00:00.000000Z",
    "started_at": "2026-07-09T16:59:24.000000Z",
    "completed_at": "2026-07-09T17:00:43.000000Z",
    "approved_at": "2026-07-09T17:02:07.000000Z",
    "status": "cancelled",
    "notes": null,
    "rejection_reason": null,
    "created_at": "2026-07-09T16:54:10.000000Z",
    "updated_at": "2026-07-09T18:45:22.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

## Response Object Reference

### Stock Count Object

| Field                  | Type            | Description                                              |
|------------------------|-----------------|----------------------------------------------------------|
| `id`                   | integer         | Unique identifier                                        |
| `count_number`         | string          | Auto-generated reference number (e.g. `SC-000001`)       |
| `warehouse_id`         | integer         | ID of the associated warehouse                           |
| `warehouse_location_id`| integer \| null | ID of the specific warehouse location (if applicable)    |
| `counted_by`           | integer         | User ID of the person performing the count               |
| `approved_by`          | integer \| null | User ID of the approver (set on approval)                |
| `created_by`           | integer         | User ID of the creator                                   |
| `count_date`           | string (ISO 8601)| Date of the physical count                              |
| `started_at`           | string \| null  | Timestamp when the count was started                     |
| `completed_at`         | string \| null  | Timestamp when the count was completed                   |
| `approved_at`          | string \| null  | Timestamp when the count was approved                    |
| `status`               | string          | Current status (`draft`, `in_progress`, `completed`, `approved`, `cancelled`) |
| `notes`                | string \| null  | Optional notes                                           |
| `rejection_reason`     | string \| null  | Reason for rejection (if applicable)                     |
| `created_at`           | string (ISO 8601)| Record creation timestamp                               |
| `updated_at`           | string (ISO 8601)| Record last updated timestamp                           |
| `deleted_at`           | string \| null  | Soft-delete timestamp                                    |
| `items`                | array           | List of counted items (see below)                        |
| `warehouse`            | object \| null  | Embedded warehouse object                                |
| `location`             | object \| null  | Embedded warehouse location object                       |

### Stock Count Item Object

| Field                  | Type            | Description                                              |
|------------------------|-----------------|----------------------------------------------------------|
| `id`                   | integer         | Unique identifier                                        |
| `stock_count_id`       | integer         | Parent stock count ID                                    |
| `item_id`              | integer         | ID of the inventory item                                 |
| `warehouse_location_id`| integer \| null | Location within the warehouse                            |
| `system_quantity`      | string (decimal)| Quantity recorded in the system at count time            |
| `counted_quantity`     | string (decimal)| Physically counted quantity                              |
| `variance_quantity`    | string (decimal)| Difference: `counted_quantity - system_quantity`         |
| `adjustment_created`   | integer (0/1)   | Whether an inventory adjustment was posted (`1` = yes)   |
| `remarks`              | string \| null  | Optional remarks for this line item                      |
| `item`                 | object \| null  | Embedded item object (included in some responses)        |

### Paginated Response Envelope

| Field          | Type    | Description                          |
|----------------|---------|--------------------------------------|
| `success`      | boolean | `true` on success                    |
| `message`      | string  | Human-readable status message        |
| `data`         | array   | Array of stock count objects         |
| `errors`       | null    | Error details (null on success)      |
| `meta.current_page` | integer | Current page number             |
| `meta.last_page`    | integer | Total number of pages           |
| `meta.per_page`     | integer | Items per page (default: 15)    |
| `meta.total`        | integer | Total number of records         |
| `links.first`  | string  | URL to the first page                |
| `links.last`   | string  | URL to the last page                 |
| `links.prev`   | string \| null | URL to the previous page      |
| `links.next`   | string \| null | URL to the next page          |
