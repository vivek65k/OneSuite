<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'inv_audit_logs';

    public $timestamps = false;

    protected $fillable = ['company_id', 'employee_id', 'action', 'entity_type', 'entity_id', 'description', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Employee::class, 'employee_id');
    }

    public static function record(int $companyId, ?int $employeeId, string $action, string $entityType, ?int $entityId, string $description): self
    {
        return self::create([
            'company_id' => $companyId, 'employee_id' => $employeeId, 'action' => $action,
            'entity_type' => $entityType, 'entity_id' => $entityId, 'description' => $description, 'created_at' => now(),
        ]);
    }
}
