<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityInspection extends Model
{
    protected $table = 'inv_quality_inspections';

    public const RESULTS = ['pass' => 'Pass', 'fail' => 'Fail', 'partial' => 'Partial'];

    protected $fillable = [
        'company_id', 'grn_line_id', 'warehouse_id', 'item_id', 'checklist_notes', 'sample_size',
        'status', 'result', 'defect_reason', 'accepted_qty', 'rejected_qty', 'inspected_at',
    ];

    protected $casts = ['inspected_at' => 'datetime'];

    public function grnLine(): BelongsTo
    {
        return $this->belongsTo(GrnLine::class, 'grn_line_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
