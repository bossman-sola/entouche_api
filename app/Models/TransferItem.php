<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferItem extends Model
{
    protected $fillable = ['transfer_id', 'item_id', 'quantity'];
}
