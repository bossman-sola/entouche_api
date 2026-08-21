<?php

namespace App\Exports;

use App\Models\StockCount;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockCountsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly array $filters = []) {}

    public function collection(): Collection
    {
        $query = StockCount::with(['warehouse', 'location', 'assignedCounter', 'items']);

        if (! empty($this->filters['ids'])) {
            return $query->whereIn('id', (array) $this->filters['ids'])->get();
        }

        $query
            ->when($this->filters['search'] ?? null, fn ($q, $v) => $q->where('count_number', 'like', "%{$v}%"))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['warehouse_id'] ?? null, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($this->filters['warehouse_location_id'] ?? null, fn ($q, $v) => $q->where('warehouse_location_id', $v))
            ->when($this->filters['count_type'] ?? null, fn ($q, $v) => $q->where('count_type', $v))
            ->when($this->filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('count_date', '>=', $v))
            ->when($this->filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('count_date', '<=', $v));

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Count No.', 'Warehouse', 'Location', 'Count Type', 'Priority',
            'Items', 'Variance (PCS)', 'Status', 'Assigned Counter',
            'Scheduled Date', 'Completed At',
        ];
    }

    public function map($stockCount): array
    {
        return [
            $stockCount->count_number,
            $stockCount->warehouse->name ?? '',
            $stockCount->location->name ?? '',
            $stockCount->count_type,
            $stockCount->priority,
            $stockCount->items->count(),
            $stockCount->items->sum('variance_quantity'),
            ucwords(str_replace('_', ' ', $stockCount->status)),
            $stockCount->assignedCounter->name ?? '',
            optional($stockCount->count_date)->toDateString(),
            optional($stockCount->completed_at)->toDateTimeString(),
        ];
    }
}
