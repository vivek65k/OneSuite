<?php

namespace App\Models\Crm;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountShare extends Model
{
    protected $table = 'crm_account_shares';

    public const ACCESS_LEVELS = ['view' => 'View Only', 'edit' => 'Can Edit'];

    protected $fillable = ['company_id', 'account_id', 'employee_id', 'team_id', 'access_level', 'shared_by_employee_id'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'shared_by_employee_id');
    }
}
