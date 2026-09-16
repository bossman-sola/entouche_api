<?php

namespace App\Services;

use App\Imports\AssetsImport;
use App\Imports\ItemsImport;
use App\Models\Import;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportService extends BaseService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function upload(UploadedFile $file, string $importType, ?int $warehouseId = null): Import
    {
        if ($importType === 'inventory' && ! $warehouseId) {
            throw new \InvalidArgumentException('A warehouse must be selected before uploading an inventory import.');
        }

        $path = $file->store("imports/{$importType}", 'local');

        $import = Import::create([
            'import_type' => $importType,
            'warehouse_id' => $warehouseId,
            'file_name' => basename($path),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'status' => 'pending',
            'uploaded_by' => auth()->id(),
        ]);

        $this->process($import);

        return $import->fresh(['errors', 'rows']);
    }

    public function getRequiredAction(string $message): string
    {
        return match ($message) {
            'Item name is required.' => 'Enter an item name in the "name" column. Also verify that the correct spreadsheet row is being used as the header row.',

            'SKU is required.' => 'Enter a SKU in the "sku" column.',

            'Unit of measure is required.' => 'Enter a valid unit of measure in the "unit" column.',

            default => 'Correct the invalid or missing data in this row and upload the corrected row again.',
        };
    }

    public function process(Import $import): void
    {
        $import->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            match ($import->import_type) {
                'items' => Excel::import(
                    new ItemsImport($import),
                    Storage::disk('local')->path(
                        $import->file_path
                    )
                ),

                'inventory' => Excel::import(
                    new AssetsImport($import),
                    Storage::disk('local')->path(
                        $import->file_path
                    )
                ),

                default => throw new \InvalidArgumentException(
                    'Unsupported import type: '
                    .$import->import_type
                ),
            };

            $import->refresh();

            $import->update([
                'total_rows' => $import->successful_rows
                    + $import->failed_rows
                    + $import->skipped_rows,

                'status' => $import->failed_rows > 0
                        ? 'partial'
                        : 'completed',

                'completed_at' => now(),
            ]);

            /*
             * The uploader is the primary recipient
             * of import result notifications.
             */
            $import->loadMissing('uploader');

            $recipient = $import->uploader;

            if ($import->status === 'completed') {
                $type = 'import_completed';

                $title = 'Import Completed';

                $message =
                    "Import of {$import->import_type} completed successfully - "
                    ."{$import->successful_rows} row(s) imported.";

            } elseif ($import->status === 'partial') {
                $type = 'import_partial';

                $title = 'Import Completed With Errors';

                $message =
                    "Import of {$import->import_type} completed with errors - "
                    ."{$import->successful_rows} row(s) imported successfully and "
                    ."{$import->failed_rows} row(s) failed out of "
                    ."{$import->total_rows} total row(s).";

            } else {
                $type = 'import_failed';

                $title = 'Import Failed';

                $message =
                    "Import of {$import->import_type} failed. "
                    ."{$import->failed_rows} row(s) failed out of "
                    ."{$import->total_rows} total row(s).";
            }

            if ($recipient) {
                $this->notifications->notifyUser(
                    $recipient,
                    $type,
                    $title,
                    $message,
                    [
                        'import_id' => $import->id,
                    ],
                );
            }
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',

                'error_summary' => $e->getMessage(),

                'completed_at' => now(),
            ]);

            $import->loadMissing('uploader');

            $recipient = $import->uploader;

            $title = 'Import Failed';

            $message =
                "Import of {$import->import_type} failed: "
                .$e->getMessage();

            if ($recipient) {
                $this->notifications->notifyUser(
                    $recipient,
                    'import_failed',
                    $title,
                    $message,
                    [
                        'import_id' => $import->id,
                    ],
                );
            }
        }
    }

    public function list(array $filters = [])
    {
        return Import::with('uploader:id,name,email,status')
            ->withCount('errors')
            ->when($filters['import_type'] ?? null, fn ($q, $v) => $q->where('import_type', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function downloadTemplate(string $importType)
    {
        $headers = match ($importType) {
            'inventory' => [
                'S/N', 'ASSET TAG NO', 'ASSET DESCRIPTION', 'EQUIPMENT SERIAL NUMBER', 'COST',
                'ASSET LIFE', 'DATE ACQUIRED', 'MANUFACTURER', 'MODEL NUMBER', 'API NUMBER',
                'FIELD LOCATION', 'VENDOR NAME', 'DELIVERY DATE TO LOCATION/YARD', 'STATUS',
                'INVOICE NUMBER FROM VENDOR', 'PO NUMBER FROM VENDOR', 'PO NUMBER ISSUED BY API',
                'PAYMENT DATE', 'NOTES',
            ],
            default => ['name', 'category', 'unit', 'item_type', 'barcode', 'reorder_level', 'unit_cost', 'description', 'brand', 'supplier'],
        };

        $sampleRows = match ($importType) {
            'inventory' => [
                [
                    1, 'AST-0001', 'Dell Latitude 5440 Laptop', 'DL5440-SN-1001', 1250,
                    4, '2024-02-15', 'Dell', 'Latitude 5440', 'API-IT-0001',
                    'Storage', 'Technology Distributors Ltd.', '2024-02-20', 'Available',
                    'INV-TD-240201', 'VPO-TD-24001', 'API-PO-24001', '2024-03-05',
                    'Assigned to general IT inventory',
                ],
                [
                    2, 'AST-0002', 'Dell Latitude 5440 Laptop', 'DL5440-SN-1002', 1250,
                    4, '2024-02-15', 'Dell', 'Latitude 5440', 'API-IT-0002',
                    'Storage', 'Technology Distributors Ltd.', '2024-02-20', 'Available',
                    'INV-TD-240201', 'VPO-TD-24001', 'API-PO-24001', '2024-03-05',
                    'Ready for allocation',
                ],
            ],
            default => [],
        };

        $lines = array_map(
            fn (array $row) => implode(',', $row),
            [$headers, ...$sampleRows],
        );

        return response(implode(PHP_EOL, $lines).PHP_EOL, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$importType}_template.csv\"",
        ]);
    }

    public function downloadErrorReport(Import $import)
    {
        $import->loadMissing('errors');

        if ($import->errors->isEmpty()) {
            throw new \InvalidArgumentException(
                'No error report is available for this import.'
            );
        }

        $filename = sprintf(
            'import-%s-error-report.csv',
            $import->id
        );

        return response()->streamDownload(function () use ($import) {
            $handle = fopen('php://output', 'w');

            // Excel UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Row Number',
                'Field',
                'Issue',
                'Required Action',
                'Row Data',
            ]);

            foreach ($import->errors as $error) {
                $requiredAction = $this->getRequiredAction(
                    $error->error_message
                );

                $rowData = $error->row_data;

                if (is_string($rowData)) {
                    $decoded = json_decode($rowData, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $rowData = $decoded;
                    }
                }

                if (is_array($rowData)) {
                    $rowData = collect($rowData)
                        ->map(function ($value, $key) {
                            $displayValue = $value === null || $value === ''
                                ? '(empty)'
                                : $value;

                            return "{$key}: {$displayValue}";
                        })
                        ->implode(' | ');
                }

                fputcsv($handle, [
                    $error->row_number,
                    $error->field ?? '—',
                    $error->error_message,
                    $requiredAction,
                    $rowData,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
