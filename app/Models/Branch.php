<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'name', 'code', 'phone', 'email',
        'address', 'city', 'state', 'country', 'postal_code',
        'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $branch): void {
            if ($branch->is_default) {
                static::query()
                    ->where('company_id', $branch->company_id)
                    ->when($branch->exists, fn ($query) => $query->whereKeyNot($branch->getKey()))
                    ->update(['is_default' => false]);
            }
        });

        static::deleted(function (self $branch): void {
            if (! $branch->is_default) {
                return;
            }

            static::query()
                ->where('company_id', $branch->company_id)
                ->oldest('id')
                ->first()
                ?->update(['is_default' => true]);
        });
    }
}
