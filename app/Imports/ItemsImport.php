<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Import;
use App\Models\ImportError;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\ItemService;
use App\Services\StockMovementService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ItemsImport implements ToCollection, WithHeadingRow, WithMultipleSheets
{
    public function __construct(
        private readonly Import $import
    ) {}

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    public function collection(Collection $rows): void
    {
        $service = app(ItemService::class);

        foreach ($rows as $index => $row) {
            try {
                // Skip completely empty Excel rows.
                if ($row->filter(fn ($value) => filled($value))->isEmpty()) {
                    continue;
                }

                /*
                 * ITEM NAME
                 * Supports: name | item_name | item_description | item_desc
                 */
                $name = $row['name']
                    ?? $row['item_name']
                    ?? $row['item_description']
                    ?? $row['item_desc']
                    ?? null;

                if (blank($name)) {
                    throw new \InvalidArgumentException(
                        'Item name is required.'
                    );
                }

                /*
                 * CATEGORY
                 * Supports: category | item_group | item_category
                 */
                $categoryName = $row['category']
                    ?? $row['item_group']
                    ?? $row['item_category']
                    ?? 'Uncategorized';

                $category = Category::firstOrCreate(
                    [
                        'name' => $categoryName,
                    ],
                    [
                        'code' => strtoupper(
                            substr(md5($categoryName), 0, 8)
                        ),
                        'status' => 'active',
                    ]
                );

                /*
                 * UNIT OF MEASURE
                 * Supports: unit | unit_of_measure | uom | u_o_m
                 */
                $unitValue = $row['unit']
                    ?? $row['unit_of_measure']
                    ?? $row['uom']
                    ?? $row['u_o_m']
                    ?? null;

                if (blank($unitValue)) {
                    throw new \InvalidArgumentException(
                        'Unit of measure is required.'
                    );
                }

                $unitName = trim($unitValue);
                $unitAbbr = trim($unitValue);

                // Supports values such as:
                // Piece (PCS)
                // Kilogram (KG)
                // Box (BOX)
                if (
                    preg_match(
                        '/^(.+?)\s*\((.+?)\)$/',
                        $unitValue,
                        $matches
                    )
                ) {
                    $unitName = trim($matches[1]);
                    $unitAbbr = trim($matches[2]);
                }

                $unit = Unit::firstOrCreate(
                    [
                        'abbreviation' => $unitAbbr,
                    ],
                    [
                        'name' => $unitName,
                        'status' => 'active',
                    ]
                );

                /*
                 * SUPPLIER
                 */
                $supplier = null;

                if (filled($row['supplier'] ?? null)) {
                    $supplierName = trim($row['supplier']);

                    $supplier = Supplier::firstOrCreate(
                        [
                            'name' => $supplierName,
                        ],
                        [
                            'code' => strtoupper(
                                substr(md5($supplierName), 0, 8)
                            ),
                            'status' => 'active',
                        ]
                    );
                }

                /*
                 * DUPLICATE BARCODE
                 *
                 * A duplicate barcode is skipped instead of treated
                 * as an import failure.
                 */
                // 'item_code' is the customer's unique identifier column (maps to barcode)
                $barcode = $row['barcode'] ?? $row['item_code'] ?? null;

                if (
                    filled($barcode) &&
                    Item::where('barcode', $barcode)->exists()
                ) {
                    $this->import->increment('skipped_rows');

                    continue;
                }

                /*
                 * CREATE ITEM
                 */
                $unitCost = (float) ($row['unit_cost'] ?? 0);

                $item = $service->create([
                    'name' => trim($name),
                    'category_id' => $category->id,
                    'unit_of_measure_id' => $unit->id,
                    'supplier_id' => $supplier?->id,
                    'item_type' => $row['item_type'] ?? 'stock_item',
                    'barcode' => $barcode,
                    'reorder_level' => $row['reorder_level'] ?? 0,
                    'unit_cost' => $unitCost,
                    'description' => $row['description'] ?? null,
                    'brand' => $row['brand'] ?? null,
                    'status' => 'active',
                ]);

                \Illuminate\Support\Facades\Log::info(
                    "Import: item created",
                    [
                        'item_id'   => $item->id,
                        'item_sku'  => $item->sku,
                        'item_name' => $item->name,
                        'deleted_at' => $item->deleted_at,
                        'status'    => $item->status,
                    ]
                );

                /*
                 * OPENING STOCK
                 *
                 * If a "Quantity on Hand" (or "quantity_on_hand") column
                 * is present and greater than zero, record an opening-stock
                 * transaction so the balance is set immediately on import.
                 */
                $openingQty = (float) (
                    $row['quantity_on_hand']
                    ?? $row['quantity on hand']
                    ?? $row['opening_stock']
                    ?? 0
                );

                if ($openingQty > 0) {
                    /*
                     * Resolve warehouse: use the "Warehouse" /
                     * "Warehouse Code" column if supplied, otherwise
                     * fall back to the first active warehouse.
                     */
                    $warehouseValue = trim(
                        $row['warehouse']
                            ?? $row['warehouse_code']
                            ?? $row['warehouse code']
                            ?? ''
                    );

                    $warehouse = filled($warehouseValue)
                        ? Warehouse::firstOrCreate(
                            ['code' => strtoupper($warehouseValue)],
                            [
                                'name' => $warehouseValue,
                                'status' => 'active',
                            ]
                        )
                        : Warehouse::where('status', 'active')->first();

                    if ($warehouse) {
                        $stockService = app(StockMovementService::class);

                        try {
                            $stockService->move([
                                'item_id' => $item->id,
                                'warehouse_id' => $warehouse->id,
                                'transaction_type' => 'opening_balance',
                                'direction' => 'in',
                                'quantity' => $openingQty,
                                'unit_cost' => $unitCost,
                                'total_value' => $openingQty * $unitCost,
                                'remarks' => 'Opening stock set during bulk import.',
                            ]);
                            \Illuminate\Support\Facades\Log::info(
                                "Import: opening stock set for item {$item->id}, qty={$openingQty}"
                            );
                        } catch (\Throwable $e) {
                            // Opening stock failure is non-fatal —
                            // the item is already created; stock can
                            // be adjusted manually afterwards.
                            \Illuminate\Support\Facades\Log::warning(
                                "Import: opening stock failed for item {$item->id}: {$e->getMessage()}"
                            );
                        }
                    }
                }

                // Verify item still exists in DB before marking success
                $exists = \App\Models\Item::withTrashed()->where('id', $item->id)->first();
                \Illuminate\Support\Facades\Log::info(
                    "Import: before increment successful_rows",
                    [
                        'item_id'    => $item->id,
                        'db_exists'  => $exists ? 'yes' : 'no',
                        'db_deleted' => $exists?->deleted_at ? (string) $exists->deleted_at : 'null',
                        'db_status'  => $exists?->status,
                    ]
                );

                $this->import->increment('successful_rows');
            } catch (\Throwable $e) {
                $this->import->increment('failed_rows');

                /*
                 * Heading row = row 1.
                 * Collection index starts from 0.
                 */
                $rowNumber = $index + 2;

                $errorMessage = $e->getMessage();

                /*
                 * Determine which spreadsheet field caused
                 * the validation failure.
                 */
                $field = match ($errorMessage) {
                    'Item name is required.' => 'name',

                    'Unit of measure is required.' =>
                        'unit',

                    'SKU is required.' =>
                        'sku',

                    default => null,
                };

                ImportError::create([
                    'import_id' => $this->import->id,
                    'row_number' => $rowNumber,
                    'field' => $field,
                    'error_message' => $errorMessage,

                    /*
                     * Store a plain array rather than the
                     * Laravel Excel row collection.
                     */
                    'row_data' => $row->toArray(),
                ]);
            }
        }
    }
}