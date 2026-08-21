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

    public function process(Import $import): void
    {
        $import->update(['status' => 'processing', 'started_at' => now()]);

        try {
            match ($import->import_type) {
                'items' => Excel::import(new ItemsImport($import), Storage::disk('local')->path($import->file_path)),
                'inventory' => Excel::import(new AssetsImport($import), Storage::disk('local')->path($import->file_path)),
                default => throw new \InvalidArgumentException('Unsupported import type: '.$import->import_type),
            };

            $import->refresh();
            $import->update([
                'total_rows' => $import->successful_rows + $import->failed_rows + $import->skipped_rows,
                'status' => $import->failed_rows > 0 ? 'partial' : 'completed',
                'completed_at' => now(),
            ]);

            if ($import->status === 'completed') {
                $this->notifications->notifyAdmins(
                    'import_completed',
                    'Import Completed',
                    "Import of {$import->import_type} completed successfully - {$import->successful_rows} row(s) imported.",
                    ['import_id' => $import->id],
                );
            } else {
                $this->notifications->notifyAdmins(
                    'import_failed',
                    'Import Failed',
                    "Import of {$import->import_type} finished with {$import->failed_rows} failed row(s) out of {$import->total_rows}.",
                    ['import_id' => $import->id],
                );
            }
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error_summary' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $this->notifications->notifyAdmins(
                'import_failed',
                'Import Failed',
                "Import of {$import->import_type} failed: {$e->getMessage()}",
                ['import_id' => $import->id],
            );
        }
    }

    public function list(array $filters = [])
    {
        return Import::with('uploader:id,name')
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
}
