<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryStructure extends Model
{
    use HasFactory, ScopedByEmployee;

    protected $fillable = ['company_id', 'employee_id', 'effective_date', 'is_current'];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalaryStructureItem::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $structure): void {
            if ($structure->is_current) {
                static::query()
                    ->where('employee_id', $structure->employee_id)
                    ->when($structure->exists, fn ($query) => $query->whereKeyNot($structure->getKey()))
                    ->update(['is_current' => false]);
            }
        });
    }
}
