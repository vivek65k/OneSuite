<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * How many levels of managers one request type has to pass at a client -
 * see App\Support\ApprovalFlow. No row means one level.
 */
class ApprovalLevelSetting extends Model
{
    protected $fillable = ['company_id', 'request_type', 'levels'];

    protected function casts(): array
    {
        return ['levels' => 'integer'];
    }
}
