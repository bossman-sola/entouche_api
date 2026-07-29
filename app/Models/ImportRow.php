<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportRow extends Model
{
    protected $fillable = [
        'import_id',
        'row_number',
        'status',
        'asset_id',
        'error_message',
        'row_data',
    ];

    protected function casts(): array
    {
        return ['row_data' => 'array'];
    }

    public function import()
    {
        return $this->belongsTo(Import::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
