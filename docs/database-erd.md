# Inventory API Database Schema / ERD

```mermaid
erDiagram
    users ||--o{ model_has_roles : has
    roles ||--o{ model_has_roles : assigned
    roles ||--o{ role_has_permissions : has
    permissions ||--o{ role_has_permissions : assigned

    categories ||--o{ items : groups
    units ||--o{ items : measures
    suppliers ||--o{ items : supplies
    warehouses ||--o{ locations : contains
    items ||--o{ inventory : stocked
    warehouses ||--o{ inventory : stores
    locations ||--o{ inventory : placed

    receipts ||--o{ receipt_items : contains
    suppliers ||--o{ receipts : provides
    warehouses ||--o{ receipts : receives
    items ||--o{ receipt_items : received

    transfers ||--o{ transfer_items : contains
    warehouses ||--o{ transfers : from_to
    items ||--o{ transfer_items : transferred

    adjustments ||--o{ adjustment_items : contains
    warehouses ||--o{ adjustments : adjusts
    items ||--o{ adjustment_items : adjusted

    stock_counts ||--o{ stock_count_items : contains
    warehouses ||--o{ stock_counts : counted
    items ||--o{ stock_count_items : counted

    users ||--o{ receipts : creates
    users ||--o{ transfers : creates
    users ||--o{ adjustments : creates
    users ||--o{ stock_counts : creates
    users ||--o{ audit_logs : performs
```

Core tables:

- `users`: application users for Passport login and Spatie role assignment.
- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`: Spatie authorization tables.
- `categories`, `units`, `suppliers`, `warehouses`, `locations`: master data.
- `items`: SKU-managed product/item catalog with optional uploaded image.
- `inventory`: current stock per item, warehouse, and optional location.
- `receipts`, `receipt_items`: inbound stock documents.
- `transfers`, `transfer_items`: stock movement between warehouses/locations.
- `adjustments`, `adjustment_items`: manual stock corrections.
- `stock_counts`, `stock_count_items`: physical count sessions and variances.
- `audit_logs`: request/action trail.
