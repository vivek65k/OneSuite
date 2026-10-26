<?php

namespace App\Models\Crm;

use App\Models\Employee;
use App\Models\Inventory\NumberingSeries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotation extends Model
{
    protected $table = 'crm_quotations';

    public const STATUSES = [
        'draft' => 'Draft',
        'pending_approval' => 'Pending Approval',
        'sent' => 'Sent',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
    ];

    protected $fillable = [
        'company_id', 'quotation_number', 'account_id', 'contact_id', 'opportunity_id', 'price_book_id',
        'status', 'valid_until', 'owner_employee_id', 'subtotal', 'discount_percent', 'discount_amount',
        'tax_percent', 'total', 'notes',
    ];

    protected function casts(): array
    {
        return ['valid_until' => 'date'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function priceBook(): BelongsTo
    {
        return $this->belongsTo(PriceBook::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuotationLine::class);
    }

    public function discountApproval(): HasOne
    {
        return $this->hasOne(DiscountApproval::class)->latestOfMany();
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(QuotationVersion::class)->orderByDesc('version_number');
    }

    public static function nextNumber(int $companyId): string
    {
        return NumberingSeries::next($companyId, 'quotation', 'Q', function () use ($companyId) {
            $count = self::where('company_id', $companyId)->count() + 1;

            return 'Q-'.now()->format('Y').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Reads the company's configurable discount approval threshold
     * (Sales → Settings & Configuration) instead of a hardcoded constant -
     * shared by both the CRM and Sales Quotation controllers.
     */
    public function exceedsDiscountThreshold(): bool
    {
        $threshold = \App\Models\Sales\SalesSetting::forCompany($this->company_id)->discount_approval_threshold_percent;

        return $this->discount_percent > $threshold;
    }

    /**
     * Recalculates subtotal/total from the current line items - called
     * after any line is added, changed, or removed so the header totals
     * never drift out of sync with what's actually on the quote. Every
     * call also appends an immutable version snapshot, regardless of
     * which controller (CRM or Sales) triggered the change.
     */
    public function recalculateTotals(): void
    {
        $subtotal = $this->lines()->get()->sum(fn ($line) => $line->quantity * $line->unit_price * (1 - $line->discount_percent / 100));
        $discountAmount = $subtotal * $this->discount_percent / 100;
        $afterDiscount = $subtotal - $discountAmount;
        $total = $afterDiscount * (1 + $this->tax_percent / 100);

        $this->update(['subtotal' => $subtotal, 'discount_amount' => $discountAmount, 'total' => $total]);
        $this->snapshotVersion();
    }

    public function snapshotVersion(): void
    {
        $nextVersion = ($this->versions()->max('version_number') ?? 0) + 1;

        QuotationVersion::create([
            'quotation_id' => $this->id,
            'version_number' => $nextVersion,
            'snapshot' => [
                'status' => $this->status,
                'discount_percent' => $this->discount_percent,
                'tax_percent' => $this->tax_percent,
                'subtotal' => $this->subtotal,
                'discount_amount' => $this->discount_amount,
                'total' => $this->total,
                'notes' => $this->notes,
                'lines' => $this->lines()->get()->map(fn ($line) => [
                    'description' => $line->description, 'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price, 'discount_percent' => $line->discount_percent, 'line_total' => $line->line_total,
                ])->all(),
            ],
            'changed_by_employee_id' => auth()->user()?->employee?->id,
            'created_at' => now(),
        ]);
    }
}
