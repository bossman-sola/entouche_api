<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Import;
use App\Services\ImportService;
use Illuminate\Http\Request;

class ImportController extends BaseApiController
{
    public function __construct(private readonly ImportService $imports)
    {
    }

    public function index(Request $request)
    {
        return $this->paginated($this->imports->list($request->all()));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls'],
            'import_type' => ['required', 'in:items,inventory'],
        ]);

        return $this->created($this->imports->upload($data['file'], $data['import_type']), 'Import uploaded');
    }

    public function show(Import $import)
    {
        return $this->success($import->load('errors'));
    }

    public function downloadTemplate(string $type)
    {
        return $this->imports->downloadTemplate($type);
    }
}
