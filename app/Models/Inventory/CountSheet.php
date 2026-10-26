<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CountSheet extends Model
{
    protected $table = 'inv_count_sheets';

    public const STATUSES = ['counting' => 'Counting', 'variance_review' => 'Variance Review', 'approved' => 'Approved', 'cancelled' => 'Cancelled'];

    protected $fillable = ['company_id', 'warehouse_id', 'schedule_id', 'count_number', 'type', 'blind_count', 'status'];

    protected $casts = ['blind_count' => 'boolean'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(CountSchedule::class, 'schedule_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CountSheetLine::class, 'sheet_id');
    }

    public static function nextNumber(int $companyId): string
    {
        return NumberingSeries::next($companyId, 'count_sheet', 'CNT', function () use ($companyId) {
            $count = self::where('company_id', $companyId)->count() + 1;

            return 'CNT-'.now()->format('Y').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
        });
    }
}
