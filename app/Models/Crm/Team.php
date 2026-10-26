<?php

namespace App\Models\Crm;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $table = 'crm_teams';

    protected $fillable = ['company_id', 'name', 'manager_employee_id'];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_employee_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'crm_team_members', 'team_id', 'employee_id')->withTimestamps();
    }

    public function territories(): HasMany
    {
        return $this->hasMany(Territory::class, 'team_id');
    }
}
