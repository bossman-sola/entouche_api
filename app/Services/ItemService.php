<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ItemService extends BaseService
{
    public function list(array $filters = [])
    {
        return Item::with(['category', 'unit', 'supplier'])
            ->withSum('stockBalances as total_stock', 'quantity_on_hand')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%")
                    ->orWhere('barcode', 'like', "%{$s}%");
            }))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($filters['item_type'] ?? null, fn ($q, $v) => $q->where('item_type', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data, ?UploadedFile $image = null): Item
    {
        return $this->transaction(function () use ($data, $image): Item {
            unset($data['sku']);
            $data['sku'] = $this->generateSku();
            $data['created_by'] = auth()->id();

            if ($image) {
                $data['image_path'] = $image->store('items', 'public');
            }

            $item = Item::create($data);
            activity()->causedBy(auth()->user())->performedOn($item)->log('item.created');

            return $item->load(['category', 'unit', 'supplier']);
        });
    }

    public function update(Item $item, array $data, ?UploadedFile $image = null): Item
    {
        return $this->transaction(function () use ($item, $data, $image): Item {
            unset($data['sku']);

            if ($image) {
                if ($item->image_path) {
                    Storage::disk('public')->delete($item->image_path);
                }
                $data['image_path'] = $image->store("items/{$item->id}", 'public');
            }

            $item->update($data);
            activity()->causedBy(auth()->user())->performedOn($item)->log('item.updated');

            return $item->fresh(['category', 'unit', 'supplier']);
        });
    }

    public function uploadImage(Item $item, UploadedFile $file): Item
    {
        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }

        $item->update(['image_path' => $file->store("items/{$item->id}", 'public')]);

        return $item->fresh();
    }

    public function removeImage(Item $item): Item
    {
        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
            $item->update(['image_path' => null]);
        }

        return $item->fresh();
    }

    public function generateSku(): string
    {
        return $this->generateNumber('items', 'sku', Setting::get('numbering.item_prefix', 'ITM'), 6);
    }

    public function getStockBalance(Item $item): array
    {
        $balances = $item->stockBalances()->with(['warehouse', 'location'])->get();

        return [
            'total_on_hand' => $balances->sum('quantity_on_hand'),
            'total_reserved' => $balances->sum('quantity_reserved'),
            'total_available' => $balances->sum('quantity_available'),
            'by_location' => $balances,
        ];
    }
}
