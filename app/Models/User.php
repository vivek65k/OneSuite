<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Scopes\EmployeeDataScope;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'password', 'is_super_admin', 'app_access', 'default_app',
    'company_id', 'is_platform_admin',
    'employee_code', 'avatar', 'phone', 'gender', 'date_of_birth',
    'designation', 'department', 'joining_date', 'is_active',
    'address', 'city', 'state', 'country', 'postal_code',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_platform_admin' => 'boolean',
            'is_active' => 'boolean',
            'app_access' => 'array',
            'date_of_birth' => 'date',
            'joining_date' => 'date',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class);
    }

    /**
     * Which employee a login belongs to is identity, not employee data to
     * browse - so it is never limited by a role's data access. Otherwise a
     * login whose employee is outside the viewer's team would look unlinked.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class)->withoutGlobalScope(EmployeeDataScope::class);
    }

    protected static function booted(): void
    {
        // The client's user limit (Company::max_users), enforced here so no
        // path can get around it - Settings > Users, the Filament panel, or
        // reactivating a deactivated user all save through this model. Only
        // a save that ADDS an active login to a client is checked; editing
        // an already-active user never trips it, even if the client is
        // over its limit (e.g. the limit was lowered after the fact).
        static::saving(function (self $user): void {
            if (! $user->company_id || ! $user->is_active) {
                return;
            }

            $addsActiveLogin = ! $user->exists
                || $user->isDirty('company_id')
                || ($user->isDirty('is_active') && ! $user->getOriginal('is_active'));

            if (! $addsActiveLogin) {
                return;
            }

            $company = Company::find($user->company_id);

            if ($company?->hasUserLimit() && $company->activeUserCount($user->id) >= $company->max_users) {
                throw ValidationException::withMessages([
                    'user_limit' => "{$company->name} has reached its limit of {$company->max_users} active ".Str::plural('user', $company->max_users).'. Deactivate an existing user or ask your OneSuite provider to raise the limit.',
                ]);
            }
        });

        static::saved(function (self $user): void {
            // wasChanged() is false on a brand new record (there's no
            // prior value to diff against) even when is_super_admin was
            // set to true right there in the same create() call - only
            // checking it here meant a freshly created super admin never
            // actually got the role, silently, unless they were saved a
            // second time later.
            if (! $user->wasRecentlyCreated && ! $user->wasChanged('is_super_admin')) {
                return;
            }

            $user->is_super_admin
                ? $user->assignRole('super_admin')
                : $user->removeRole('super_admin');
        });
    }
}
