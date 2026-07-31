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
        'warehouse_id' => [
            'required_if:import_type,inventory',
            'nullable',
            'exists:warehouses,id',
        ],
    ]);

    try {
        $import = $this->imports->upload(
            $data['file'],
            $data['import_type'],
            $data['warehouse_id'] ?? null
        );
    } catch (\InvalidArgumentException $e) {
        return $this->error(
            $e->getMessage(),
            [
                'required_action' =>
                    'Correct the spreadsheet data and upload the file again.',
            ],
            422
        );
    }

    // Ensure the latest import totals and errors are available.
    $import->refresh();
    $import->loadMissing('errors');

    $rectifications = $import->errors
        ->map(function ($error) {
            $requiredAction = match ($error->error_message) {
                'Item name is required.' =>
                    'Enter an item name in the "name" column. Also verify that the correct spreadsheet row is being used as the header row.',

                'SKU is required.' =>
                    'Enter a SKU in the "sku" column.',

                'Unit of measure is required.' =>
                    'Enter a valid unit of measure in the "unit_of_measure" column.',

                default =>
                    'Correct the invalid or missing data in this row and upload the corrected row again.',
            };

            return [
                'row_number' => $error->row_number,
                'field' => $error->field,
                'issue' => $error->error_message,
                'required_action' => $requiredAction,
                'row_data' => $error->row_data,
            ];
        })
        ->values();

    /*
     * Complete import: all processed rows succeeded.
     */
    if ((int) $import->failed_rows === 0) {
        return $this->created(
            $import,
            'Import completed successfully.'
        );
    }

    /*
     * Partial import: some rows succeeded and some failed.
     */
    if ((int) $import->successful_rows > 0) {
        return response()->json([
            'success' => false,
            'message' => sprintf(
                'Import partially completed. %d row(s) succeeded, %d failed, and %d were skipped. Correct the listed errors and upload only the corrected failed rows again.',
                $import->successful_rows,
                $import->failed_rows,
                $import->skipped_rows
            ),
            'data' => $import,
            'errors' => $rectifications,
        ], 207);
    }

    /*
     * Failed import: no rows were imported.
     */
    return response()->json([
        'success' => false,
        'message' =>
            'Import failed. No items were created. Correct the listed spreadsheet errors and upload the file again.',
        'data' => $import,
        'errors' => $rectifications,
    ], 422);
}

    public function show(Import $import)
    {
        return $this->success($import->load(['errors', 'rows.asset', 'warehouse', 'uploader:id,name']));
    }

    public function downloadTemplate(string $type)
    {
        return $this->imports->downloadTemplate($type);
    }
}
