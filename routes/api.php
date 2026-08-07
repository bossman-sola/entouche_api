<?php

use App\Http\Controllers\Api\V1\AdjustmentController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ImportController;
use App\Http\Controllers\Api\V1\ItemController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ReceiptController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\StockCountController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\TransferController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:api')->group(function (): void {
        Route::prefix('auth')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::post('refresh', [AuthController::class, 'refresh']);
        });

        Route::apiResource('users', UserController::class)->middleware('permission:users.manage');
        Route::post('users/{user}/assign-role', [UserController::class, 'assignRole'])->middleware('permission:users.manage');
        Route::post('users/{user}/remove-role', [UserController::class, 'removeRole'])->middleware('permission:users.manage');
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:users.manage');

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);

        Route::get('roles', [RoleController::class, 'index'])->middleware('permission:users.manage');
        Route::get('roles/{role}', [RoleController::class, 'show'])->middleware('permission:users.manage');
        Route::get('permissions', [RoleController::class, 'permissions'])->middleware('permission:users.manage');

        Route::apiResource('categories', CategoryController::class)->middleware('permission:master-data.manage');
        Route::post('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->middleware('permission:master-data.manage');

        Route::apiResource('units', UnitController::class)->middleware('permission:master-data.manage');
        Route::post('units/{unit}/toggle-status', [UnitController::class, 'toggleStatus'])->middleware('permission:master-data.manage');

        Route::apiResource('warehouses', WarehouseController::class)->middleware('permission:master-data.manage');
        Route::post('warehouses/{warehouse}/toggle-status', [WarehouseController::class, 'toggleStatus'])->middleware('permission:master-data.manage');
        // Route::get('warehouses/{warehouse}/locations', [WarehouseController::class, 'locations'])->middleware('permission:master-data.manage');
        Route::get('warehouses/{warehouse}/stock-summary', [WarehouseController::class, 'stockSummary'])->middleware('permission:reports.view');

        Route::apiResource('warehouses.locations', LocationController::class)->shallow()->middleware('permission:master-data.manage');
        Route::post('locations/{location}/toggle-status', [LocationController::class, 'toggleStatus'])->middleware('permission:master-data.manage');

        Route::apiResource('suppliers', SupplierController::class)->middleware('permission:master-data.manage');
        Route::post('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->middleware('permission:master-data.manage');

        Route::apiResource('items', ItemController::class)->middleware('permission:items.view');
        Route::post('items/{item}/image', [ItemController::class, 'uploadImage'])->middleware('permission:items.edit');
        Route::delete('items/{item}/image', [ItemController::class, 'removeImage'])->middleware('permission:items.edit');
        Route::get('items/{item}/transactions', [ItemController::class, 'transactions'])->middleware('permission:items.view');
        Route::get('items/{item}/stock-balance', [ItemController::class, 'stockBalance'])->middleware('permission:items.view');
        Route::post('items/{item}/toggle-status', [ItemController::class, 'toggleStatus'])->middleware('permission:items.edit');

        Route::get('transactions', [TransactionController::class, 'index'])->middleware('permission:reports.view');
        Route::get('transactions/{txn}', [TransactionController::class, 'show'])->middleware('permission:reports.view');

        Route::apiResource('receipts', ReceiptController::class)->middleware('permission:receipts.view');
        Route::post('receipts/{receipt}/approve', [ReceiptController::class, 'approve'])->middleware('permission:receipts.approve');
        Route::post('receipts/{receipt}/receive', [ReceiptController::class, 'receive'])->middleware('permission:receipts.approve');
        Route::post('receipts/{receipt}/cancel', [ReceiptController::class, 'cancel'])->middleware('permission:receipts.create');

        Route::apiResource('transfers', TransferController::class)->middleware('permission:transfers.view');
        Route::post('transfers/{transfer}/submit', [TransferController::class, 'submit'])->middleware('permission:transfers.create');
        Route::post('transfers/{transfer}/approve', [TransferController::class, 'approve'])->middleware('permission:transfers.approve');
        Route::post('transfers/{transfer}/reject', [TransferController::class, 'reject'])->middleware('permission:transfers.approve');
        Route::post('transfers/{transfer}/complete', [TransferController::class, 'complete'])->middleware('permission:transfers.approve');
        Route::post('transfers/{transfer}/cancel', [TransferController::class, 'cancel'])->middleware('permission:transfers.create');

        Route::apiResource('adjustments', AdjustmentController::class)->middleware('permission:adjustments.view');
        Route::post('adjustments/{adjustment}/submit', [AdjustmentController::class, 'submit'])->middleware('permission:adjustments.create');
        Route::post('adjustments/{adjustment}/approve', [AdjustmentController::class, 'approve'])->middleware('permission:adjustments.approve');
        Route::post('adjustments/{adjustment}/reject', [AdjustmentController::class, 'reject'])->middleware('permission:adjustments.approve');
        Route::post('adjustments/{adjustment}/cancel', [AdjustmentController::class, 'cancel'])->middleware('permission:adjustments.create');


        Route::get('stock-counts/overview', [StockCountController::class, 'overview'])->middleware('permission:stock_counts.view');
        Route::get('stock-counts/lookups', [StockCountController::class, 'lookups'])->middleware('permission:stock_counts.view');
        Route::get('stock-counts/calendar', [StockCountController::class, 'calendar'])->middleware('permission:stock_counts.view');
        Route::get('stock-counts/export', [StockCountController::class, 'export'])->middleware('permission:stock_counts.view');
        Route::get('stock-counts/items/search', [StockCountController::class, 'searchItems'])->middleware('permission:stock_counts.view');

        Route::apiResource('stock-counts', StockCountController::class)->parameters(['stock-counts' => 'stockCount'])->middleware('permission:stock_counts.view');
        Route::post('stock-counts/{stockCount}/cancel', [StockCountController::class, 'cancel'])->middleware('permission:stock_counts.create');


        Route::post('stock-counts/{stockCount}/items', [StockCountController::class, 'addItems'])->middleware('permission:stock_counts.create');
        Route::patch('stock-counts/{stockCount}/items/{item}', [StockCountController::class, 'updateItem'])->middleware('permission:stock_counts.create');
        Route::delete('stock-counts/{stockCount}/items/{item}', [StockCountController::class, 'removeItem'])->middleware('permission:stock_counts.create');


        Route::get('stock-counts/{stockCount}/progress', [StockCountController::class, 'progress'])->middleware('permission:stock_counts.view');
        Route::get('stock-counts/{stockCount}/variance', [StockCountController::class, 'varianceBreakdown'])->middleware('permission:stock_counts.view');
        Route::post('stock-counts/{stockCount}/submit', [StockCountController::class, 'submit'])->middleware('permission:stock_counts.create');
        Route::post('stock-counts/{stockCount}/approve', [StockCountController::class, 'approve'])->middleware('permission:stock_counts.approve');
        Route::post('stock-counts/{stockCount}/reject', [StockCountController::class, 'reject'])->middleware('permission:stock_counts.approve');
        Route::post('stock-counts/{stockCount}/request-recount', [StockCountController::class, 'requestRecount'])->middleware('permission:stock_counts.approve');

        Route::prefix('reports')->middleware('permission:reports.view')->group(function (): void {
            Route::get('dashboard-stats', [ReportController::class, 'dashboardStats']);
            Route::get('stock-summary', [ReportController::class, 'stockSummary']);
            Route::get('movement-report', [ReportController::class, 'movementReport']);
            Route::get('low-stock', [ReportController::class, 'lowStock']);
        });

        Route::post('imports', [ImportController::class, 'store'])->middleware('permission:data_import.manage');
        Route::get('imports', [ImportController::class, 'index'])->middleware('permission:data_import.manage');
        Route::get('imports/{import}', [ImportController::class, 'show'])->middleware('permission:data_import.manage');
        Route::get('imports/templates/{type}', [ImportController::class, 'downloadTemplate'])->middleware('permission:data_import.manage');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit_logs.view');
        Route::get('audit-logs/{log}', [AuditLogController::class, 'show'])->middleware('permission:audit_logs.view');

        Route::get('settings', [SettingController::class, 'index'])->middleware('permission:settings.manage');
        Route::put('settings', [SettingController::class, 'bulkUpdate'])->middleware('permission:settings.manage');
        Route::get('settings/{key}', [SettingController::class, 'show'])->middleware('permission:settings.manage');
    });
});


