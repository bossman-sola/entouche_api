<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetLocationHistory;
use App\Models\Import;
use App\Models\Item;
use App\Models\Location;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AssetImportService extends BaseService
{
    public function __construct(private readonly StockMovementService $stock) {}

    /**
     * @throws \Throwable
     */
    public function importRow(Import $import, Collection $row): Asset
    {
        return $this->transaction(function () use ($import, $row): Asset {
            $assetTag = trim((string) ($row['asset_tag_no'] ?? ''));
            if ($assetTag === '') {
                throw new \InvalidArgumentException('ASSET TAG NO is required.');
            }

            $description = trim((string) ($row['asset_description'] ?? ''));
            if ($description === '') {
                throw new \InvalidArgumentException('ASSET DESCRIPTION is required.');
            }

            if (Asset::where('asset_tag', $assetTag)->exists()) {
                throw new \InvalidArgumentException("Asset tag \"{$assetTag}\" already exists.");
            }

            $item = $this->resolveItem($description, $row['manufacturer'] ?? null, $row['model_number'] ?? null);
            $location = $this->resolveLocation($import->warehouse_id, $row['field_location'] ?? null);
            $supplier = $this->resolveSupplier($row['vendor_name'] ?? null);
            $cost = $this->toDecimal($row['cost'] ?? 0);

            $receipt = $this->resolveReceipt($import->warehouse_id, $location, $supplier, $row);

            $asset = Asset::create([
                'asset_tag' => $assetTag,
                'serial_number' => $row['equipment_serial_number'] ?? null,
                'api_number' => $row['api_number'] ?? null,
                'acquisition_cost' => $cost,
                'asset_life' => $row['asset_life'] ?? null,
                'date_acquired' => $this->toDate($row['date_acquired'] ?? null),
                'status' => $this->normalizeStatus($row['status'] ?? null),
                'notes' => $row['notes'] ?? null,
                'item_id' => $item->id,
                'warehouse_id' => $import->warehouse_id,
                'location_id' => $location?->id,
                'supplier_id' => $supplier?->id,
                'import_id' => $import->id,
            ]);

            if ($receipt) {
                ReceiptItem::create([
                    'receipt_id' => $receipt->id,
                    'item_id' => $item->id,
                    'warehouse_location_id' => $location?->id,
                    'quantity' => 1,
                    'unit_cost' => $cost,
                    'total_cost' => $cost,
                ]);
            }

            $this->stock->move([
                'item_id' => $item->id,
                'warehouse_id' => $import->warehouse_id,
                'warehouse_location_id' => $location?->id,
                'transaction_type' => 'opening_balance',
                'direction' => 'in',
                'quantity' => 1,
                'unit_cost' => $cost,
                'total_value' => $cost,
                'reference_type' => Asset::class,
                'reference_id' => $asset->id,
                'remarks' => 'Opening balance from inventory import '.$import->file_name,
            ]);

            AssetLocationHistory::create([
                'asset_id' => $asset->id,
                'warehouse_id' => $import->warehouse_id,
                'location_id' => $location?->id,
                'moved_at' => $asset->date_acquired ?? now(),
                'moved_by' => auth()->id(),
                'reference_type' => Import::class,
                'reference_id' => $import->id,
                'notes' => 'Initial location from inventory import',
            ]);

            return $asset;
        });
    }

    private function resolveItem(string $description, ?string $manufacturer, ?string $modelNumber): Item
    {
        $manufacturer = filled($manufacturer) ? trim($manufacturer) : null;
        $modelNumber = filled($modelNumber) ? trim($modelNumber) : null;

        $item = Item::where('name', $description)
            ->when($manufacturer === null, fn ($q) => $q->whereNull('manufacturer'), fn ($q) => $q->where('manufacturer', $manufacturer))
            ->when($modelNumber === null, fn ($q) => $q->whereNull('model_number'), fn ($q) => $q->where('model_number', $modelNumber))
            ->first();

        if ($item) {
            return $item;
        }

        $unit = Unit::firstOrCreate(['abbreviation' => 'EA'], ['name' => 'Each', 'status' => 'active']);

        return Item::create([
            'name' => $description,
            'manufacturer' => $manufacturer,
            'model_number' => $modelNumber,
            'unit_of_measure_id' => $unit->id,
            'sku' => app(ItemService::class)->generateSku(),
            'item_type' => 'asset',
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);
    }

    private function resolveLocation(int $warehouseId, ?string $name): ?Location
    {
        $name = filled($name) ? trim($name) : null;

        if ($name === null) {
            return null;
        }

        return Location::firstOrCreate(
            ['warehouse_id' => $warehouseId, 'name' => $name],
            ['code' => strtoupper(substr(md5($warehouseId.$name), 0, 8)), 'type' => 'field', 'status' => 'active'],
        );
    }

    private function resolveSupplier(?string $name): ?Supplier
    {
        $name = filled($name) ? trim($name) : null;

        if ($name === null) {
            return null;
        }

        return Supplier::firstOrCreate(
            ['name' => $name],
            ['code' => strtoupper(substr(md5($name), 0, 8)), 'status' => 'active'],
        );
    }

    private function resolveReceipt(int $warehouseId, ?Location $location, ?Supplier $supplier, Collection $row): ?Receipt
    {
        $invoiceNumber = filled($row['invoice_number_from_vendor'] ?? null) ? trim($row['invoice_number_from_vendor']) : null;
        $deliveryDate = $this->toDate($row['delivery_date_to_location_yard'] ?? null);

        if (! $supplier && ! $invoiceNumber) {
            return null;
        }

        $receiptDate = $deliveryDate ?? now()->toDateString();

        $receipt = Receipt::where('warehouse_id', $warehouseId)
            ->where('receipt_date', $receiptDate)
            ->when($supplier === null, fn ($q) => $q->whereNull('supplier_id'), fn ($q) => $q->where('supplier_id', $supplier->id))
            ->when($invoiceNumber === null, fn ($q) => $q->whereNull('invoice_number'), fn ($q) => $q->where('invoice_number', $invoiceNumber))
            ->first();

        if ($receipt) {
            return $receipt;
        }

        return Receipt::create([
            'receipt_number' => $this->generateNumber('receipts', 'receipt_number', Setting::get('numbering.receipt_prefix', 'RCT'), 6),
            'supplier_id' => $supplier?->id,
            'warehouse_id' => $warehouseId,
            'invoice_number' => $invoiceNumber,
            'receipt_date' => $receiptDate,
            'receiving_location_id' => $location?->id,
            'vendor_po_number' => $row['po_number_from_vendor'] ?? null,
            'api_po_number' => $row['po_number_issued_by_api'] ?? null,
            'payment_date' => $this->toDate($row['payment_date'] ?? null),
            'received_by' => auth()->id(),
            'created_by' => auth()->id(),
            'received_at' => now(),
            'status' => 'received',
        ]);
    }

    private function normalizeStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        return match (true) {
            $status === '' => 'active',
            str_contains($status, 'retire') => 'retired',
            str_contains($status, 'repair') || str_contains($status, 'maint') => 'under_maintenance',
            str_contains($status, 'inactive') || str_contains($status, 'disposed') => 'inactive',
            default => 'active',
        };
    }

    private function toDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {

            if (is_numeric($value)) {
                return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }

            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function toDecimal(mixed $value): float
    {
        if (blank($value)) {
            return 0.0;
        }

        return (float) preg_replace('/[^0-9.\-]/', '', (string) $value);
    }
}
