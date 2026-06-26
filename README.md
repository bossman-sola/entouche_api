# entouch_api

Laravel InventoryPro API for Entouch.

## Included

- Database schema and ERD: `docs/database-erd.md`
- Passport auth foundation: login, logout, me
- Spatie permissions: roles, permissions, middleware aliases
- Global API response format:
  - `success`
  - `message`
  - `data`
  - `errors`
- Users CRUD and role assignment
- Master data CRUD:
  - categories
  - units
  - warehouses
  - locations
  - suppliers
- Items module:
  - create item
  - auto-generate SKU
  - upload item image
  - list/filter/search
  - edit/view/delete

## Setup

## Setup

```bash
php composer.phar install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan passport:install --force
php artisan storage:link
php artisan serve
```

Default seeded admin:

```text
email: admin@inventory.local
password: Admin@1234
```

## API Routes

All routes are prefixed with `/api/v1`.

```text
POST   /auth/login
POST   /auth/logout
GET    /auth/me

GET    /users
POST   /users
GET    /users/{user}
PUT    /users/{user}
DELETE /users/{user}
POST   /users/{user}/roles

GET    /roles
POST   /roles
GET    /roles/{role}
PUT    /roles/{role}
DELETE /roles/{role}

GET/POST/GET/PUT/DELETE /categories
GET/POST/GET/PUT/DELETE /units
GET/POST/GET/PUT/DELETE /warehouses
GET/POST/GET/PUT/DELETE /locations
GET/POST/GET/PUT/DELETE /suppliers
GET/POST/GET/PUT/DELETE /items
```
