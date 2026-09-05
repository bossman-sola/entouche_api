<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Import;
use App\Models\ImportError;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\ItemService;
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
                 */
                $name = $row['name'] ?? $row['item_name'] ?? null;

                if (blank($name)) {
                    throw new \InvalidArgumentException(
                        'Item name is required.'
                    );
                }

                /*
                 * CATEGORY
                 */
                $categoryName = $row['category'] ?? 'Uncategorized';

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
                 */
                $unitValue = $row['unit'] ?? $row['unit_of_measure'] ?? null;

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
                $barcode = $row['barcode'] ?? null;

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
                $service->create([
                    'name' => trim($name),
                    'category_id' => $category->id,
                    'unit_of_measure_id' => $unit->id,
                    'supplier_id' => $supplier?->id,
                    'item_type' => $row['item_type'] ?? 'stock_item',
                    'barcode' => $barcode,
                    'reorder_level' => $row['reorder_level'] ?? 0,
                    'unit_cost' => $row['unit_cost'] ?? 0,
                    'description' => $row['description'] ?? null,
                    'brand' => $row['brand'] ?? null,
                    'status' => 'active',
                ]);

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