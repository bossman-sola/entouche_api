<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportError extends Model
{
    protected $fillable = ['import_id', 'row_number', 'field', 'error_type', 'error_message', 'row_data'];

    protected function casts(): array
    {
        return ['row_data' => 'array'];
    }
}
