<?php

namespace App\Imports;

use App\Models\Import;
use App\Models\ImportRow;
use App\Services\AssetImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetsImport implements ToCollection, WithHeadingRow
{
    public function __construct(private readonly Import $import) {}

    public function collection(Collection $rows): void
    {
        $service = app(AssetImportService::class);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if ($row->filter(fn ($value) => filled($value))->isEmpty()) {
                continue;
            }

            try {
                $asset = $service->importRow($this->import, $row);

                $this->import->increment('successful_rows');
                ImportRow::create([
                    'import_id' => $this->import->id,
                    'row_number' => $rowNumber,
                    'status' => 'success',
                    'asset_id' => $asset->id,
                    'row_data' => $row->toArray(),
                ]);
            } catch (\Throwable $e) {
                $this->import->increment('failed_rows');
                ImportRow::create([
                    'import_id' => $this->import->id,
                    'row_number' => $rowNumber,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'row_data' => $row->toArray(),
                ]);
            }
        }
    }
}
