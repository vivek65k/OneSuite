<?php

namespace App\Models\Crm;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CrmRole extends Model
{
    protected $table = 'crm_roles';

    public const PERMISSIONS = [
        'view_leads' => 'View Leads', 'edit_leads' => 'Edit Leads', 'delete_leads' => 'Delete Leads',
        'view_opportunities' => 'View Opportunities', 'edit_opportunities' => 'Edit Opportunities',
        'approve_discounts' => 'Approve Discounts', 'manage_campaigns' => 'Manage Campaigns',
        'view_reports' => 'View Reports', 'manage_configuration' => 'Manage CRM Configuration',
    ];

    protected $fillable = ['company_id', 'name', 'permissions'];

    protected $casts = ['permissions' => 'array'];

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'crm_role_assignments', 'role_id', 'employee_id')->withTimestamps();
    }
}
