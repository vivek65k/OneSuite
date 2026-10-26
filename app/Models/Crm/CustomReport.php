<?php

namespace App\Models\Crm;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomReport extends Model
{
    protected $table = 'crm_custom_reports';

    public const SOURCES = [
        'leads' => 'Leads',
        'opportunities' => 'Opportunities',
        'accounts' => 'Accounts',
        'cases' => 'Cases',
        'campaigns' => 'Campaigns',
    ];

    public const SOURCE_COLUMNS = [
        'leads' => ['name', 'email', 'status', 'score', 'created_at'],
        'opportunities' => ['name', 'amount', 'status', 'probability', 'expected_close_date'],
        'accounts' => ['name', 'industry', 'created_at'],
        'cases' => ['case_number', 'subject', 'status', 'priority', 'created_at'],
        'campaigns' => ['name', 'type', 'status', 'sent_count', 'opened_count'],
    ];

    // Text/enum fields usable as pivot rows, pivot columns, or a chart's x-axis grouping.
    public const SOURCE_GROUP_FIELDS = [
        'leads' => ['status', 'lead_source_id'],
        'opportunities' => ['status', 'sales_stage_id'],
        'accounts' => ['industry', 'customer_segment_id'],
        'cases' => ['status', 'priority', 'type'],
        'campaigns' => ['type', 'status'],
    ];

    // Numeric fields usable as the measure that gets summed/averaged/counted.
    public const SOURCE_MEASURE_FIELDS = [
        'leads' => ['score'],
        'opportunities' => ['amount', 'probability'],
        'accounts' => [],
        'cases' => [],
        'campaigns' => ['sent_count', 'opened_count', 'clicked_count', 'budget', 'actual_cost'],
    ];

    public const REPORT_TYPES = ['table' => 'Table', 'pivot' => 'Pivot Table', 'chart' => 'Chart'];

    public const CHART_TYPES = ['bar' => 'Bar', 'line' => 'Line', 'pie' => 'Pie'];

    public const AGGREGATES = ['sum' => 'Sum', 'avg' => 'Average', 'count' => 'Count'];

    protected $fillable = ['company_id', 'name', 'source', 'report_type', 'config', 'columns', 'filters', 'owner_employee_id'];

    protected $casts = ['columns' => 'array', 'filters' => 'array', 'config' => 'array'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id');
    }
}
