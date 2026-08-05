# Transfers Module

The Transfers module manages the movement of inventory items between warehouses and/or locations. A transfer follows a lifecycle: **draft → pending_approval → approved → completed** (or **rejected** / **cancelled** at various stages).

---

## Table of Contents

1. [Transfer Object](#transfer-object)
2. [Transfer Statuses](#transfer-statuses)
3. [Endpoints Overview](#endpoints-overview)
4. [List Transfers](#1-list-transfers)
5. [Create Transfer](#2-create-transfer)
6. [Get Transfer](#3-get-transfer)
7. [Update Transfer](#4-update-transfer)
8. [Delete Transfer](#5-delete-transfer)
9. [Submit Transfer](#6-submit-transfer)
10. [Approve Transfer](#7-approve-transfer)
11. [Reject Transfer](#8-reject-transfer)
12. [Complete Transfer](#9-complete-transfer)
13. [Cancel Transfer](#10-cancel-transfer)

---

## Transfer Object

| Field               | Type            | Description                                              |
|---------------------|-----------------|----------------------------------------------------------|
| `id`                | integer         | Unique identifier                                        |
| `transfer_number`   | string          | Auto-generated reference number (e.g. `TRF-000001`)     |
| `from_warehouse_id` | integer         | Source warehouse ID                                      |
| `from_location_id`  | integer\|null   | Source location within the warehouse (optional)          |
| `to_warehouse_id`   | integer         | Destination warehouse ID                                 |
| `to_location_id`    | integer\|null   | Destination location within the warehouse (optional)     |
| `requested_by`      | integer         | User ID who requested the transfer                       |
| `approved_by`       | integer\|null   | User ID who approved/rejected the transfer               |
| `created_by`        | integer         | User ID who created the record                           |
| `transfer_date`     | string\|null    | ISO 8601 date of the transfer                            |
| `approved_at`       | string\|null    | ISO 8601 timestamp of approval                           |
| `completed_at`      | string\|null    | ISO 8601 timestamp of completion                         |
| `status`            | string          | Current status (see [Transfer Statuses](#transfer-statuses)) |
| `notes`             | string\|null    | Optional notes                                           |
| `rejection_reason`  | string\|null    | Reason provided when rejecting                           |
| `created_at`        | string          | ISO 8601 creation timestamp                              |
| `updated_at`        | string          | ISO 8601 last-updated timestamp                          |
| `deleted_at`        | string\|null    | ISO 8601 soft-delete timestamp                           |
| `items`             | array           | Line items included in the transfer                      |

### Transfer Item Object

| Field         | Type    | Description                          |
|---------------|---------|--------------------------------------|
| `id`          | integer | Unique identifier                    |
| `transfer_id` | integer | Parent transfer ID                   |
| `item_id`     | integer | Inventory item ID                    |
| `quantity`    | string  | Quantity as a decimal string         |
| `created_at`  | string  | ISO 8601 creation timestamp          |
| `updated_at`  | string  | ISO 8601 last-updated timestamp      |

---

## Transfer Statuses

| Status             | Description                                              |
|--------------------|----------------------------------------------------------|
| `draft`            | Created but not yet submitted for approval               |
| `pending_approval` | Submitted and awaiting approval                          |
| `approved`         | Approved and ready to be completed                       |
| `completed`        | Transfer has been executed; stock has moved              |
| `rejected`         | Rejected by an approver                                  |
| `cancelled`        | Cancelled after creation                                 |

---

## Endpoints Overview

| # | Method   | Endpoint                                          | Description          |
|---|----------|---------------------------------------------------|----------------------|
| 1 | `GET`    | `/api/v1/transfers`                               | List Transfers       |
| 2 | `POST`   | `/api/v1/transfers`                               | Create Transfer      |
| 3 | `GET`    | `/api/v1/transfers/{transfer_id}`                 | Get Transfer         |
| 4 | `PUT`    | `/api/v1/transfers/{transfer_id}`                 | Update Transfer      |
| 5 | `DELETE` | `/api/v1/transfers/{transfer_id}`                 | Delete Transfer      |
| 6 | `POST`   | `/api/v1/transfers/{transfer_id}/submit`          | Submit Transfer      |
| 7 | `POST`   | `/api/v1/transfers/{transfer_id}/approve`         | Approve Transfer     |
| 8 | `POST`   | `/api/v1/transfers/{transfer_id}/reject`          | Reject Transfer      |
| 9 | `POST`   | `/api/v1/transfers/{transfer_id}/complete`        | Complete Transfer    |
|10 | `POST`   | `/api/v1/transfers/{transfer_id}/cancel`          | Cancel Transfer      |

> **Authentication:** All endpoints require a Bearer token via the `Authorization` header.

---

## 1. List Transfers

Retrieve a paginated list of all transfers.

### Request

```
GET {{base_url}}/api/v1/transfers?page=1
```

**Headers**

| Key           | Value              |
|---------------|--------------------|
| `Accept`      | `application/json` |
| `Authorization` | `Bearer {{access_token}}` |

**Query Parameters**

| Parameter | Type    | Required | Description              |
|-----------|---------|----------|--------------------------|
| `page`    | integer | No       | Page number (default: 1) |

### Sample Response — 200 OK (with data)

```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": 1,
      "transfer_number": "TRF-000001",
      "from_warehouse_id": 1,
      "from_location_id": null,
      "to_warehouse_id": 2,
      "to_location_id": null,
      "requested_by": 2,
      "approved_by": null,
      "created_by": 2,
      "transfer_date": null,
      "approved_at": null,
      "completed_at": null,
      "status": "draft",
      "notes": "Transfer to secondary warehouse",
      "rejection_reason": null,
      "created_at": "2026-07-09T20:43:03.000000Z",
      "updated_at": "2026-07-09T20:43:03.000000Z",
      "deleted_at": null,
      "items": [
        {
          "id": 1,
          "transfer_id": 1,
          "item_id": 3,
          "quantity": "50.000",
          "created_at": "2026-07-09T20:43:03.000000Z",
          "updated_at": "2026-07-09T20:43:03.000000Z"
        }
      ]
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
    "first": "http://127.0.0.1:8000/api/v1/transfers?page=1",
    "last": "http://127.0.0.1:8000/api/v1/transfers?page=1",
    "prev": null,
    "next": null
  }
}
```

### Sample Response — 200 OK (empty)

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
    "first": "http://127.0.0.1:8000/api/v1/transfers?page=1",
    "last": "http://127.0.0.1:8000/api/v1/transfers?page=1",
    "prev": null,
    "next": null
  }
}
```

---

## 2. Create Transfer

Create a new transfer in `draft` status.

### Request

```
POST {{base_url}}/api/v1/transfers
```

**Headers**

| Key             | Value              |
|-----------------|--------------------|
| `Accept`        | `application/json` |
| `Content-Type`  | `application/json` |
| `Authorization` | `Bearer {{access_token}}` |

**Request Body**

```json
{
  "from_warehouse_id": 1,
  "from_location_id": 1,
  "to_warehouse_id": 1,
  "to_location_id": 2,
  "notes": "Move from Receiving Area to Storage Area",
  "items": [
    { "item_id": 3, "quantity": 50 }
  ]
}
```

**Body Parameters**

| Field               | Type    | Required | Description                                      |
|---------------------|---------|----------|--------------------------------------------------|
| `from_warehouse_id` | integer | Yes      | Source warehouse ID                              |
| `from_location_id`  | integer | No       | Source location ID within the warehouse          |
| `to_warehouse_id`   | integer | Yes      | Destination warehouse ID                         |
| `to_location_id`    | integer | No       | Destination location ID within the warehouse     |
| `notes`             | string  | No       | Optional notes about the transfer                |
| `items`             | array   | Yes      | Array of items to transfer                       |
| `items[].item_id`   | integer | Yes      | Inventory item ID                                |
| `items[].quantity`  | number  | Yes      | Quantity to transfer                             |

### Sample Response — 201 Created

```json
{
  "success": true,
  "message": "Transfer created",
  "data": {
    "from_warehouse_id": 1,
    "from_location_id": 1,
    "to_warehouse_id": 1,
    "to_location_id": 2,
    "notes": "Move from Receiving Area to Storage Area",
    "transfer_number": "TRF-000008",
    "transfer_date": "2026-07-10T00:00:00.000000Z",
    "requested_by": 2,
    "created_by": 2,
    "status": "draft",
    "updated_at": "2026-07-10T03:49:19.000000Z",
    "created_at": "2026-07-10T03:49:19.000000Z",
    "id": 8,
    "items": [
      {
        "id": 16,
        "transfer_id": 8,
        "item_id": 3,
        "quantity": "50.000",
        "created_at": "2026-07-10T03:49:19.000000Z",
        "updated_at": "2026-07-10T03:49:19.000000Z"
      }
    ]
  },
  "errors": null
}
```

---

## 3. Get Transfer

Retrieve a single transfer by its ID.

### Request

```
GET {{base_url}}/api/v1/transfers/{{transfer_id}}
```

**Headers**

| Key             | Value              |
|-----------------|--------------------|
| `Accept`        | `application/json` |
| `Authorization` | `Bearer {{access_token}}` |

**Path Parameters**

| Parameter     | Type    | Required | Description     |
|---------------|---------|----------|-----------------|
| `transfer_id` | integer | Yes      | Transfer ID     |

### Sample Response — 200 OK

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": 1,
    "transfer_number": "TRF-000001",
    "from_warehouse_id": 1,
    "from_location_id": null,
    "to_warehouse_id": 2,
    "to_location_id": null,
    "requested_by": 2,
    "approved_by": null,
    "created_by": 2,
    "transfer_date": null,
    "approved_at": null,
    "completed_at": null,
    "status": "draft",
    "notes": "Transfer to secondary warehouse",
    "rejection_reason": null,
    "created_at": "2026-07-09T20:43:03.000000Z",
    "updated_at": "2026-07-09T20:43:03.000000Z",
    "deleted_at": null,
    "items": [
      {
        "id": 1,
        "transfer_id": 1,
        "item_id": 3,
        "quantity": "50.000",
        "created_at": "2026-07-09T20:43:03.000000Z",
        "updated_at": "2026-07-09T20:43:03.000000Z"
      }
    ]
  },
  "errors": null
}
```

---

## 4. Update Transfer

Update a transfer that is still in `draft` status.

### Request

```
PUT {{base_url}}/api/v1/transfers/{{transfer_id}}
```

**Headers**

| Key             | Value              |
|-----------------|--------------------|
| `Accept`        | `application/json` |
| `Content-Type`  | `application/json` |
| `Authorization` | `Bearer {{access_token}}` |

**Path Parameters**

| Parameter     | Type    | Required | Description  |
|---------------|---------|----------|--------------|
| `transfer_id` | integer | Yes      | Transfer ID  |

**Request Body**

```json
{
  "notes": "Updated transfer notes",
  "from_location_id": 1,
  "items": [
    { "item_id": 6, "warehouse_location_id": 2, "quantity": 60, "unit_cost": 25.00 }
  ]
}
```

**Body Parameters**

| Field                          | Type    | Required | Description                                  |
|--------------------------------|---------|----------|----------------------------------------------|
| `notes`                        | string  | No       | Updated notes                                |
| `from_location_id`             | integer | No       | Updated source location ID                   |
| `items`                        | array   | No       | Replacement list of transfer items           |
| `items[].item_id`              | integer | Yes      | Inventory item ID                            |
| `items[].warehouse_location_id`| integer | No       | Location ID within the warehouse             |
| `items[].quantity`             | number  | Yes      | Quantity to transfer                         |
| `items[].unit_cost`            | number  | No       | Unit cost of the item                        |

### Sample Response — 200 OK

```json
{
  "success": true,
  "message": "Transfer updated",
  "data": {
    "id": 1,
    "transfer_number": "TRF-000001",
    "from_warehouse_id": 1,
    "from_location_id": null,
    "to_warehouse_id": 2,
    "to_location_id": null,
    "requested_by": 2,
    "approved_by": null,
    "created_by": 2,
    "transfer_date": null,
    "approved_at": null,
    "completed_at": null,
    "status": "draft",
    "notes": "Updated transfer notes",
    "rejection_reason": null,
    "created_at": "2026-07-09T20:43:03.000000Z",
    "updated_at": "2026-07-09T20:45:41.000000Z",
    "deleted_at": null,
    "items": [
      {
        "id": 8,
        "transfer_id": 1,
        "item_id": 5,
        "quantity": "50.000",
        "created_at": "2026-07-09T21:02:28.000000Z",
        "updated_at": "2026-07-09T21:06:32.000000Z"
      },
      {
        "id": 9,
        "transfer_id": 1,
        "item_id": 6,
        "quantity": "60.000",
        "created_at": "2026-07-09T21:06:40.000000Z",
        "updated_at": "2026-07-09T21:06:54.000000Z"
      }
    ]
  },
  "errors": null
}
```

---

## 5. Delete Transfer

Soft-delete a transfer. Only transfers in `draft` status can be deleted.

### Request

```
DELETE {{base_url}}/api/v1/transfers/{{transfer_id}}
```

**Headers**

| Key             | Value              |
|-----------------|--------------------|
| `Accept`        | `application/json` |
| `Authorization` | `Bearer {{access_token}}` |

**Path Parameters**

| Parameter     | Type    | Required | Description  |
|---------------|---------|----------|--------------|
| `transfer_id` | integer | Yes      | Transfer ID  |

### Sample Response — 200 OK

```json
{
  "success": true,
  "message": "Transfer deleted",
  "data": null,
  "errors": null
}
```

---

## 6. Submit Transfer

Submit a `draft` transfer for approval. Status transitions to `pending_approval`.

### Request

```
POST {{base_url}}/api/v1/transfers/{{transfer_id}}/submit
```

**Headers**

| Key             | Value              |
|-----------------|--------------------|
| `Accept`        | `application/json` |
| `Authorization` | `Bearer {{access_token}}` |

**Path Parameters**

| Parameter     | Type    | Required | Description  |
|---------------|---------|----------|--------------|
| `transfer_id` | integer | Yes      | Transfer ID  |

### Sample Response — 200 OK

```json
{
  "success": true,
  "message": "Transfer submitted",
  "data": {
    "id": 2,
    "transfer_number": "TRF-000002",
    "from_warehouse_id": 1,
    "from_location_id": null,
    "to_warehouse_id": 2,
    "to_location_id": null,
    "requested_by": 2,
    "approved_by": null,
    "created_by": 2,
    "transfer_date": null,
    "approved_at": null,
    "completed_at": null,
    "status": "pending_approval",
    "notes": "Transfer to secondary warehouse",
    "rejection_reason": null,
    "created_at": "2026-07-09T21:07:54.000000Z",
    "updated_at": "2026-07-09T21:08:16.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

## 7. Approve Transfer

Approve a `pending_approval` transfer. Status transitions to `approved`.

### Request

```
POST {{base_url}}/api/v1/transfers/{{transfer_id}}/approve
```

**Headers**

| Key             | Value              |
|-----------------|--------------------|
| `Accept`        | `application/json` |
| `Authorization` | `Bearer {{access_token}}` |

**Path Parameters**

| Parameter     | Type    | Required | Description  |
|---------------|---------|----------|--------------|
| `transfer_id` | integer | Yes      | Transfer ID  |

### Sample Response — 200 OK

```json
{
  "success": true,
  "message": "Transfer approved",
  "data": {
    "id": 6,
    "transfer_number": "TRF-000006",
    "from_warehouse_id": 1,
    "from_location_id": 2,
    "to_warehouse_id": 2,
    "to_location_id": 2,
    "requested_by": 2,
    "approved_by": 2,
    "created_by": 2,
    "transfer_date": null,
    "approved_at": "2026-07-10T03:29:15.000000Z",
    "completed_at": null,
    "status": "approved",
    "notes": "Transfer to secondary warehouse",
    "rejection_reason": null,
    "created_at": "2026-07-10T03:27:44.000000Z",
    "updated_at": "2026-07-10T03:29:15.000000Z",
    "deleted_at": null,
    "items": [
      {
        "id": 14,
        "transfer_id": 6,
        "item_id": 3,
        "quantity": "50.000",
        "created_at": "2026-07-10T03:27:44.000000Z",
        "updated_at": "2026-07-10T03:27:44.000000Z"
      }
    ]
  },
  "errors": null
}
```

---

## 8. Reject Transfer

Reject a `pending_approval` transfer with a reason. Status transitions to `rejected`.

### Request

```
POST {{base_url}}/api/v1/transfers/{{transfer_id}}/reject
```

**Headers**

| Key             | Value              |
|-----------------|--------------------|
| `Accept`        | `application/json` |
| `Content-Type`  | `application/json` |
| `Authorization` | `Bearer {{access_token}}` |

**Path Parameters**

| Parameter     | Type    | Required | Description  |
|---------------|---------|----------|--------------|
| `transfer_id` | integer | Yes      | Transfer ID  |

**Request Body**

```json
{
  "reason": "Insufficient stock"
}
```

**Body Parameters**

| Field    | Type   | Required | Description              |
|----------|--------|----------|--------------------------|
| `reason` | string | Yes      | Reason for rejection     |

### Sample Response — 200 OK

```json
{
  "success": true,
  "message": "Transfer rejected",
  "data": {
    "id": 2,
    "transfer_number": "TRF-000002",
    "from_warehouse_id": 1,
    "from_location_id": null,
    "to_warehouse_id": 2,
    "to_location_id": null,
    "requested_by": 2,
    "approved_by": 2,
    "created_by": 2,
    "transfer_date": null,
    "approved_at": "2026-07-09T21:08:56.000000Z",
    "completed_at": null,
    "status": "rejected",
    "notes": "Transfer to secondary warehouse",
    "rejection_reason": "Insufficient stock",
    "created_at": "2026-07-09T21:07:54.000000Z",
    "updated_at": "2026-07-09T21:09:30.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

## 9. Complete Transfer

Mark an `approved` transfer as completed. This executes the stock movement. Status transitions to `completed`.

> **Note:** Only transfers with `approved` status can be completed. Attempting to complete a non-approved transfer returns a `422 Unprocessable Content` error.

### Request

```
POST {{base_url}}/api/v1/transfers/{{transfer_id}}/complete
```

**Headers**

| Key             | Value              |
|-----------------|--------------------|
| `Accept`        | `application/json` |
| `Authorization` | `Bearer {{access_token}}` |

**Path Parameters**

| Parameter     | Type    | Required | Description  |
|---------------|---------|----------|--------------|
| `transfer_id` | integer | Yes      | Transfer ID  |

### Sample Response — 200 OK

```json
{
  "success": true,
  "message": "Transfer completed",
  "data": {
    "id": 7,
    "transfer_number": "TRF-000007",
    "from_warehouse_id": 1,
    "from_location_id": 1,
    "to_warehouse_id": 1,
    "to_location_id": 2,
    "requested_by": 2,
    "approved_by": 2,
    "created_by": 2,
    "transfer_date": null,
    "approved_at": "2026-07-10T03:42:47.000000Z",
    "completed_at": "2026-07-10T03:43:08.000000Z",
    "status": "completed",
    "notes": "Move from Receiving Area to Storage Area",
    "rejection_reason": null,
    "created_at": "2026-07-10T03:41:44.000000Z",
    "updated_at": "2026-07-10T03:43:08.000000Z",
    "deleted_at": null,
    "items": [
      {
        "id": 15,
        "transfer_id": 7,
        "item_id": 3,
        "quantity": "50.000",
        "created_at": "2026-07-10T03:41:44.000000Z",
        "updated_at": "2026-07-10T03:41:44.000000Z"
      }
    ]
  },
  "errors": null
}
```

### Sample Response — 422 Unprocessable Content (transfer not approved)

```json
{
  "success": false,
  "message": "Only approved transfers can be completed.",
  "data": null,
  "errors": null
}
```

---

## 10. Cancel Transfer

Cancel a transfer. Status transitions to `cancelled`.

### Request

```
POST {{base_url}}/api/v1/transfers/{{transfer_id}}/cancel
```

**Headers**

| Key             | Value              |
|-----------------|--------------------|
| `Accept`        | `application/json` |
| `Authorization` | `Bearer {{access_token}}` |

**Path Parameters**

| Parameter     | Type    | Required | Description  |
|---------------|---------|----------|--------------|
| `transfer_id` | integer | Yes      | Transfer ID  |

### Sample Response — 200 OK

```json
{
  "success": true,
  "message": "Transfer cancelled",
  "data": {
    "id": 7,
    "transfer_number": "TRF-000007",
    "from_warehouse_id": 1,
    "from_location_id": 1,
    "to_warehouse_id": 1,
    "to_location_id": 2,
    "requested_by": 2,
    "approved_by": 2,
    "created_by": 2,
    "transfer_date": null,
    "approved_at": "2026-07-10T03:42:47.000000Z",
    "completed_at": "2026-07-10T03:43:08.000000Z",
    "status": "cancelled",
    "notes": "Move from Receiving Area to Storage Area",
    "rejection_reason": null,
    "created_at": "2026-07-10T03:41:44.000000Z",
    "updated_at": "2026-07-10T03:45:05.000000Z",
    "deleted_at": null
  },
  "errors": null
}
```

---

## Transfer Lifecycle

```
         ┌─────────────────────────────────────────────────────┐
         │                                                     │
  [Create] → draft → [Submit] → pending_approval → [Approve] → approved → [Complete] → completed
                                       │                           │
                                  [Reject]                    [Cancel]
                                       │                           │
                                   rejected                   cancelled
```

> Transfers in `draft` status can also be directly **deleted** (soft-delete).
