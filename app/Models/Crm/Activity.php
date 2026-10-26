<?php

namespace App\Models\Crm;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    protected $table = 'crm_activities';

    public const TYPES = [
        'task' => 'Task',
        'call' => 'Call',
        'meeting' => 'Meeting',
        'email' => 'Email',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    /**
     * What an activity can be logged against - used both to validate
     * related_type on save and to build the "related record" picker that's
     * shared across Tasks/Calls/Meetings/Emails/Interaction History, and
     * (via the Sales-side keys below) the Sales app's own Activities hub.
     */
    public const RELATABLE = [
        'lead' => Lead::class,
        'account' => Account::class,
        'contact' => Contact::class,
        'opportunity' => Opportunity::class,
        'sales_order' => SalesOrder::class,
        'quotation' => Quotation::class,
        'contract' => Contract::class,
    ];

    /**
     * The column each RELATABLE model uses as its display label -
     * defaults to 'name'; only models without a 'name' column need an
     * entry here.
     */
    public const RELATABLE_LABEL_FIELDS = [
        'sales_order' => 'order_number',
        'quotation' => 'quotation_number',
        'contract' => 'title',
    ];

    public const RECURRENCE_FREQUENCIES = ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];

    protected $fillable = [
        'company_id', 'type', 'subject', 'description', 'related_type', 'related_id',
        'due_at', 'start_at', 'end_at', 'status', 'priority', 'direction',
        'owner_employee_id', 'reminder_at', 'completed_at',
        'is_recurring', 'recurrence_frequency', 'recurrence_parent_id',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'reminder_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_recurring' => 'boolean',
        ];
    }

    public function computeNextOccurrence(): \Illuminate\Support\Carbon
    {
        $base = $this->due_at ?? now();

        return match ($this->recurrence_frequency) {
            'weekly' => $base->copy()->addWeek(),
            'monthly' => $base->copy()->addMonth(),
            default => $base->copy()->addDay(),
        };
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function notes()
    {
        return $this->morphMany(Note::class, 'notable');
    }
}
