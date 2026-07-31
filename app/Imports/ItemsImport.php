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

class ItemsImport implements ToCollection, WithHeadingRow
{
    public function __construct(private readonly Import $import)
    {
    }
   public function sheets(): array
    {
        return [0 => $this];
    }

    public function collection(Collection $rows): void
    {
      
        $service = app(ItemService::class);

        foreach ($rows as $index => $row) {
            try {
                // NEW: support both template heading styles
                $name = $row['name'] ?? $row['item_name'] ?? null;

                if (blank($name)) {                                    // CHANGED: was $row['name']
                    throw new \InvalidArgumentException('Item name is required.');
                }

                $category = Category::firstOrCreate(
                    ['name' => $row['category'] ?? 'Uncategorized'],
                    ['code' => strtoupper(substr(md5($row['category'] ?? 'UNC'), 0, 8)), 'status' => 'active']
                );

                // NEW: parse "Piece (PCS)" style unit values
                $unitName = $row['unit'] ?? 'Piece';
                $unitAbbr = $unitName;
                if (preg_match('/^(.+?)\s*\((.+?)\)$/', $unitName, $m)) {
                    $unitName = trim($m[1]);
                    $unitAbbr = trim($m[2]);
                }

                $unit = Unit::firstOrCreate(                            // CHANGED: uses parsed values
                    ['abbreviation' => $unitAbbr ?: 'PCS'],
                    ['name' => $unitName ?: 'Piece', 'status' => 'active']
                );

                $supplier = null;
                if (filled($row['supplier'] ?? null)) {
                    $supplier = Supplier::firstOrCreate(
                        ['name' => $row['supplier']],
                        ['code' => strtoupper(substr(md5($row['supplier']), 0, 8)), 'status' => 'active']
                    );
                }

                if (Item::where('barcode', $row['barcode'] ?? null)->whereNotNull('barcode')->exists()) {
                    $this->import->increment('skipped_rows');
                    continue;
                }

                $service->create([
                    'name' => $name,                                    // CHANGED: was $row['name']
                    'category_id' => $category->id,
                    'unit_of_measure_id' => $unit->id,
                    'supplier_id' => $supplier?->id,
                    'item_type' => $row['item_type'] ?? 'stock_item',
                    'barcode' => $row['barcode'] ?? null,
                    'reorder_level' => $row['reorder_level'] ?? 0,
                    'unit_cost' => $row['unit_cost'] ?? 0,
                    'description' => $row['description'] ?? null,
                    'brand' => $row['brand'] ?? null,
                    'status' => 'active',
                ]);
                $this->import->increment('successful_rows');
            } catch (\Throwable $e) {
                $this->import->increment('failed_rows');
                ImportError::create([
                    'import_id' => $this->import->id,
                    'row_number' => $index + 2,
                    'error_type' => class_basename($e),
                    'error_message' => $e->getMessage(),
                    'row_data' => $row->toArray(),
                ]);
            }
        }
    }
}
