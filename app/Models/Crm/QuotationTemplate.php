<?php

namespace App\Models\Crm;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuotationTemplate extends Model
{
    protected $table = 'crm_quotation_templates';

    protected $fillable = ['company_id', 'name', 'description', 'discount_percent', 'tax_percent', 'notes', 'created_by_employee_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by_employee_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuotationTemplateLine::class, 'template_id');
    }
}
