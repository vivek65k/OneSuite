<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelBooking extends Model
{
    use HasFactory, ScopedByEmployee;

    public const TYPES = [
        'flight' => 'Flight',
        'train' => 'Train',
        'bus' => 'Bus',
        'hotel' => 'Hotel',
        'cab' => 'Cab',
        'other' => 'Other',
    ];

    protected $fillable = [
        'company_id', 'travel_request_id', 'booking_type', 'provider',
        'booking_reference', 'cost', 'travel_date', 'details',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'travel_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function travelRequest(): BelongsTo
    {
        return $this->belongsTo(TravelRequest::class);
    }

    /** A booking belongs to whoever the trip it is for belongs to. */
    public function dataScopeVia(): ?string
    {
        return 'travelRequest';
    }
}
