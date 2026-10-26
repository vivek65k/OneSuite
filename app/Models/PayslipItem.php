<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipItem extends Model
{
    use ScopedByEmployee;

    protected $fillable = ['payslip_id', 'component_name', 'type', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    /** A payslip line belongs to whoever its payslip belongs to. */
    public function dataScopeVia(): ?string
    {
        return 'payslip';
    }
}
