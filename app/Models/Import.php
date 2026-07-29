<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $fillable = [
        'import_type',
        'warehouse_id',
        'file_name',
        'file_path',
        'original_name',
        'file_size',
        'status',
        'total_rows',
        'successful_rows',
        'failed_rows',
        'skipped_rows',
        'error_summary',
        'uploaded_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function errors()
    {
        return $this->hasMany(ImportError::class);
    }

    public function rows()
    {
        return $this->hasMany(ImportRow::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
