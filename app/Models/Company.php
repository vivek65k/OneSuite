<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'legal_name', 'registration_number', 'tax_id',
        'email', 'phone', 'website', 'currency', 'timezone', 'logo',
        'footer_text', 'login_title', 'login_subtitle',
        'address', 'city', 'state', 'country', 'postal_code', 'is_active', 'app_access',
        'max_users',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'app_access' => 'array',
            'max_users' => 'integer',
        ];
    }

    /**
     * Logins that count toward max_users - active ones only, so
     * deactivating someone who left frees their seat for a replacement.
     */
    public function activeUserCount(?int $exceptUserId = null): int
    {
        return $this->users()
            ->where('is_active', true)
            ->when($exceptUserId, fn ($q) => $q->whereKeyNot($exceptUserId))
            ->count();
    }

    public function hasUserLimit(): bool
    {
        return $this->max_users !== null;
    }

    /**
     * Seats still free, or null when the client has no limit.
     */
    public function remainingUserSlots(): ?int
    {
        return $this->hasUserLimit() ? max(0, $this->max_users - $this->activeUserCount()) : null;
    }

    public function userLimitReached(): bool
    {
        return $this->hasUserLimit() && $this->activeUserCount() >= $this->max_users;
    }

    /**
     * Each client (tenant) is one row here. Resolves from the logged-in
     * user's own company_id - every one of this app's ~120 call sites
     * already scopes its queries through Company::current()->id, so this
     * one method is what actually separates one client's data from
     * another's; nothing else needed to change.
     *
     * There is deliberately no fallback to "the first company" - a
     * platform admin (company_id null) or a request with no authenticated
     * user has no tenant to resolve, and silently defaulting to some
     * other client's data here would be a real cross-tenant leak, not a
     * convenience worth having.
     */
    public static function current(): self
    {
        $user = Auth::user();

        abort_unless($user && $user->company_id, 403, 'No client context for this request.');

        // Called many times per page (every scoped query); look it up once per request.
        $key = 'onesuite.company.'.$user->company_id;
        $attributes = app()->bound('request') ? request()->attributes : null;
        if ($attributes?->has($key)) {
            return $attributes->get($key);
        }

        $company = static::findOrFail($user->company_id);
        $attributes?->set($key, $company);

        return $company;
    }

    /**
     * A null app_access means unrestricted (every app) - the default for
     * the one pre-existing client, and future-proof against new apps
     * being added later. Combined with the logged-in user's own
     * app_access in AppLauncher::hasAccess() - a user can never reach an
     * app their client hasn't been given, regardless of their own access.
     */
    public function hasApp(string $key): bool
    {
        return $this->app_access === null || in_array($key, $this->app_access, true);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * This client's super admin login(s) - the account created alongside
     * the client in /platform/clients, oldest first.
     */
    public function admins(): HasMany
    {
        return $this->hasMany(User::class)->where('is_super_admin', true)->orderBy('id');
    }

    public function mailAccount(): HasOne
    {
        return $this->hasOne(CompanyMailAccount::class);
    }

    public function businessUnits(): HasMany
    {
        return $this->hasMany(BusinessUnit::class);
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class);
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    protected static function booted(): void
    {
        static::created(function (self $company): void {
            if ($company->branches()->exists()) {
                return;
            }

            $company->branches()->create([
                'name' => 'Head Office',
                'is_default' => true,
                'is_active' => true,
            ]);
        });
    }
}
