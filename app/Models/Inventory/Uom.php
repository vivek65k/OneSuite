<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class Uom extends Model
{
    protected $table = 'inv_uoms';

    protected $fillable = ['company_id', 'name', 'code', 'decimal_precision'];
}
