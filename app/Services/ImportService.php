<?php

namespace App\Services;

use App\Imports\ItemsImport;
use App\Models\Import;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportService extends BaseService
{
    public function upload(UploadedFile $file, string $importType): Import
    {
        $path = $file->store("imports/{$importType}", 'local');

        $import = Import::create([
            'import_type' => $importType,
            'file_name' => basename($path),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'status' => 'pending',
            'uploaded_by' => auth()->id(),
        ]);

        $this->process($import);

        return $import->fresh('errors');
    }

    public function process(Import $import): void
    {
        $import->update(['status' => 'processing', 'started_at' => now()]);

        try {
            match ($import->import_type) {
                'items' => Excel::import(new ItemsImport($import), Storage::disk('local')->path($import->file_path)),
                default => throw new \InvalidArgumentException('Unsupported import type: ' . $import->import_type),
            };

            $import->refresh();
            $import->update([
                'status' => $import->failed_rows > 0 ? 'partial' : 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error_summary' => $e->getMessage(),
                'completed_at' => now(),
            ]);
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
            'inventory' => ['sku', 'warehouse', 'location', 'quantity'],
            default => ['name', 'category', 'unit', 'item_type', 'barcode', 'reorder_level', 'unit_cost', 'description', 'brand', 'supplier'],
        };

        return response(implode(',', $headers) . PHP_EOL, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$importType}_template.csv\"",
        ]);
    }
}
