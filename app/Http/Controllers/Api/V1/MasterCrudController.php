<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;

abstract class MasterCrudController extends BaseApiController
{
    protected string $model;
    protected array $rules = [];
    protected array $relations = [];

    public function index(Request $request)
    {
        $query = ($this->model)::query()->with($this->relations)->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
        }

        return $this->paginated($query->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request)
    {
        $record = ($this->model)::create($request->validate($this->rules));

        return $this->created($record->load($this->relations));
    }

    public function show($record)
    {
        $record = $this->resolveRecord($record);
        return $this->success($record->load($this->relations));
    }

    public function update(Request $request, $record)
    {
        $record = $this->resolveRecord($record);
        $record->update($request->validate($this->rules));

        return $this->success($record->fresh($this->relations), 'Updated');
    }

    public function destroy($record)
    {
        $record = $this->resolveRecord($record);
        $record->delete();

        return $this->success(null, 'Deleted');
    }

    public function toggleStatus($record)
    {
        $record = $this->resolveRecord($record);
        $record->update(['status' => $record->status === 'active' ? 'inactive' : 'active']);

        return $this->success($record->fresh(), 'Status updated');
    }

    protected function resolveRecord($record)
    {
        if ($record instanceof \Illuminate\Database\Eloquent\Model) {
            return $record;
        }

        return ($this->model)::findOrFail($record);
    }
}
