# Entouche Inventory Management System API

Backend API for the **Entouche Inventory Management System**, an enterprise inventory and warehouse management platform designed to manage inventory, warehouse operations, stock movements, approvals, reporting, notifications, and administrative workflows.

The API is built with **Laravel** and provides RESTful endpoints consumed by the Entouche Inventory Management System frontend.

---

## Features

The API currently supports:

### Authentication & User Management
- User authentication
- Laravel Passport API authentication
- Role-based access control
- Permission-based authorization
- User creation and management
- User activation and deactivation

### Inventory Management
- Item management
- Categories
- Units of measure
- Suppliers
- Inventory quantities
- Reorder levels
- Inventory valuation
- Item image management

### Warehouse Management
- Multiple warehouses
- Warehouse locations
- Receiving locations
- Storage locations
- Inventory availability by warehouse and location

### Receipts
- Create inventory receipts
- Submit receipts for approval
- Approve receipts
- Receive inventory
- Cancel receipts
- Supplier and purchase order information
- Automatic stock movement creation

### Transfers
- Warehouse-to-warehouse transfers
- Location-to-location transfers
- Transfer approval workflow
- Transfer rejection and cancellation
- Transfer completion
- Automatic inventory movement between locations

### Inventory Adjustments
- Increase and decrease adjustments
- Adjustment approval workflow
- Adjustment rejection and cancellation
- Inventory reconciliation
- Adjustment valuation

### Stock Counts
- Full stock counts
- Cycle counts
- Spot counts
- Counter assignment
- Count submission
- Variance detection
- Review and approval workflow
- Recount requests
- Automatic inventory reconciliation

### Inventory Transactions
- Central stock movement history
- Receipt movements
- Transfer movements
- Adjustment movements
- Stock count reconciliation movements
- Quantity and inventory value tracking

### Data Import
- Excel inventory imports
- Asset imports
- Import validation
- Import error tracking
- Import history
- CSV error reports

### Reports
- Inventory reporting
- Stock movement reporting
- Warehouse reporting
- Inventory valuation data
- Operational reporting

### Notifications
- In-app notifications
- Email notifications
- Approval notifications
- Transfer notifications
- Receipt notifications
- Adjustment notifications
- Stock count notifications
- Import notifications
- Maintenance notifications

### Audit Logs
- System activity tracking
- User activity tracking
- Administrative audit history

### System Settings
- Company information
- Company logo
- Warehouse settings
- Notification settings
- Security settings
- Integration settings
- Audit settings

### System Maintenance
- Scheduled maintenance windows
- Maintenance notifications
- Maintenance status management
- In-app and email maintenance alerts

---

## Technology Stack

- **PHP**
- **Laravel**
- **MySQL**
- **Laravel Passport**
- **Spatie Laravel Permission**
- **Maatwebsite Laravel Excel**
- **Cloudinary**
- **REST API**
- **Heroku**

---

## Requirements

Before running the project locally, make sure you have:

- PHP 8.2+
- Composer
- MySQL
- Git
- Laravel-compatible PHP extensions

---

## Installation

### 1. Clone the repository

```bash
git clone <repository-url>
```

Move into the project directory:

```bash
cd <project-directory>
```

### 2. Install dependencies

```bash
composer install
```

### 3. Create the environment file

Copy the example environment file:

```bash
cp .env.example .env
```

Do not commit your `.env` file to Git.

### 4. Generate the application key

```bash
php artisan key:generate
```

### 5. Configure the database

Update the database configuration in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=entouche_inventory
DB_USERNAME=root
DB_PASSWORD=
```

Create the configured database before running migrations.

### 6. Run database migrations

```bash
php artisan migrate
```

If the project seeders are required:

```bash
php artisan db:seed
```

Alternatively:

```bash
php artisan migrate --seed
```

### 7. Configure Laravel Passport

Install Passport keys and clients as required by the environment:

```bash
php artisan passport:install
```

> Do not run commands that regenerate production authentication credentials unless you understand their effect on existing clients and tokens.

### 8. Clear cached configuration

```bash
php artisan optimize:clear
```

### 9. Start the development server

```bash
php artisan serve
```

By default, Laravel will be available at:

```text
http://127.0.0.1:8000
```

The API is versioned under:

```text
/api/v1
```

---

## Environment Configuration

The application requires environment variables for services such as the database, email, authentication, and image storage.

Example:

```env
APP_NAME="Entouche Inventory Management System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=entouche_inventory
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="Entouche"

CLOUDINARY_URL=
```

Never commit real passwords, API secrets, SMTP credentials, database credentials, or other sensitive environment values to the repository.

---

## Authentication

The API uses **Laravel Passport** for API authentication.

Protected API endpoints require an access token.

Example request header:

```http
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

---

## Roles & Permissions

The system uses role-based and permission-based authorization.

The primary roles are:

### System Administrator

Full administrative access to the system.

### Warehouse Manager

Responsible for warehouse operations, approvals, inventory management, and operational oversight.

### Inventory Officer

Responsible for day-to-day inventory operations such as receipts, transfers, adjustments, stock counts, and item management.

### Management Viewer

Read-only access to operational and management information.

Permissions are enforced by the backend and should not rely solely on frontend visibility controls.

---

## Inventory Movement Architecture

Inventory changes are recorded through stock movements.

Examples include:

```text
Receipt
   ↓
Receipt Received
   ↓
Stock Movement (IN)
   ↓
Inventory Updated
```

Transfers create corresponding outgoing and incoming movements:

```text
Source Location
      ↓
Transfer OUT
      ↓
Transfer
      ↓
Transfer IN
      ↓
Destination Location
```

Adjustments and approved stock-count variances also create inventory movements to maintain an auditable inventory history.

---

## Approval Workflows

### Receipts

```text
Draft
  ↓
Submitted
  ↓
Approved
  ↓
Received
```

### Transfers

```text
Draft
  ↓
Pending Approval
  ↓
Approved
  ↓
Completed
```

### Stock Counts

```text
Draft
  ↓
Requested
  ↓
In Progress
  ↓
Pending Review
  ↓
Completed
```

Rejections, cancellations, and recount workflows may alter these flows where permitted.

---

## Notifications

The backend provides centralized notifications through the notification service.

Notifications may be delivered through:

- Database/in-app notifications
- Email notifications

Examples include:

- Receipt awaiting approval
- Receipt completed
- Transfer awaiting approval
- Transfer approved
- Transfer completed
- Adjustment awaiting approval
- Stock count assignment
- Stock count variance
- Import completed
- Import failed
- Scheduled system maintenance

Notification delivery is handled centrally to avoid duplicate notifications from individual controllers and services.

---

## Company Branding

Company branding is stored through the system settings.

Example setting:

```text
company.logo
```

Company logo images are uploaded to Cloudinary and the resulting secure URL is stored in the settings database.

The frontend can use this value across areas such as:

- Application sidebar
- Authentication screens
- Settings
- Other branded interfaces

---

## Data Import

The application supports inventory data import using Excel files.

The import process can:

- Validate spreadsheet records
- Create or reuse items
- Create inventory assets
- Create or reuse suppliers
- Create or reuse locations
- Process acquisition costs
- Record import failures
- Generate error reports
- Notify the uploading user when processing is complete

Import results may have statuses such as:

```text
processing
completed
partial
failed
```

---

## API Response Structure

Successful responses generally follow the application's API response format:

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {}
}
```

Validation or application errors may return:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {}
}
```

HTTP status codes should also be used to determine the result of API requests.

---

## Useful Laravel Commands

Clear application caches:

```bash
php artisan optimize:clear
```

Run migrations:

```bash
php artisan migrate
```

Run seeders:

```bash
php artisan db:seed
```

View registered routes:

```bash
php artisan route:list
```

View scheduled tasks:

```bash
php artisan schedule:list
```

Start the scheduler locally:

```bash
php artisan schedule:work
```

Run tests:

```bash
php artisan test
```

---

## Security

When working with this repository:

- Never commit `.env`
- Never commit production credentials
- Never expose Passport private keys
- Never commit Cloudinary API secrets
- Never commit SMTP passwords
- Never expose database credentials
- Use Laravel authorization middleware for protected operations
- Validate all incoming API requests
- Keep dependencies updated
- Use HTTPS in production

If credentials are accidentally committed, rotate them immediately rather than only removing them from the latest commit.

---

## Deployment

The API can be deployed to a Laravel-compatible cloud environment.

The current architecture supports deployment to platforms such as:

- Heroku
- AWS
- Azure
- Other PHP/Laravel hosting environments

Production environment variables must be configured directly in the deployment environment and must not be stored in the repository.

After deployment or environment configuration changes, run:

```bash
php artisan optimize:clear
```

where appropriate for the deployment environment.

---

## Project Structure

Key Laravel directories include:

```text
app/
├── Http/
│   └── Controllers/
├── Models/
├── Services/
├── Notifications/
└── Imports/

database/
├── migrations/
└── seeders/

routes/
└── api.php

config/

tests/
```

Controllers are responsible for HTTP/API interactions, while reusable business logic should be placed in services where appropriate.

---

## Frontend

This repository contains the **backend API**.

The Entouche Inventory Management System frontend is maintained separately and communicates with this application through the REST API.

---

## Development

When adding functionality:

1. Create or update the required migration/model.
2. Implement business logic in the appropriate service.
3. Add controller endpoints.
4. Add validation and authorization.
5. Register routes.
6. Add notifications where necessary.
7. Add or update tests.
8. Verify stock and financial effects for inventory-changing operations.
9. Test the corresponding frontend integration.

Inventory-changing operations should be transactional where necessary to prevent partial updates.

---

## Ownership

The Entouche Inventory Management System was designed and developed by **Skiplab Innovation**.

The underlying software, source code, reusable components, and intellectual property are subject to the applicable software development and licensing agreement.

Unauthorized copying, redistribution, modification, resale, or commercial use of this source code is prohibited unless expressly permitted by the applicable agreement.

---

## Developed By

**Skiplab Innovation**

Software solutions for modern business operations.

---

## License

This software is proprietary.

Copyright © 2026 **Skiplab Innovation**. All rights reserved.

Use, distribution, modification, sublicensing, or reproduction of this software is subject to the terms of the applicable licensing or software development agreement.