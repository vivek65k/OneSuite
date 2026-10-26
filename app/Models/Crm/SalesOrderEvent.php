<?php

namespace App\Models\Crm;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderEvent extends Model
{
    protected $table = 'crm_sales_order_events';

    public $timestamps = false;

    public const TYPES = ['status_change' => 'Status Change', 'amendment' => 'Amendment', 'cancellation' => 'Cancellation', 'fulfilment' => 'Fulfilment'];

    protected $fillable = ['sales_order_id', 'event_type', 'description', 'employee_id', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public static function log(int $salesOrderId, string $type, string $description, ?int $employeeId = null): self
    {
        return self::create(['sales_order_id' => $salesOrderId, 'event_type' => $type, 'description' => $description, 'employee_id' => $employeeId, 'created_at' => now()]);
    }
}
