<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NumberingSeries extends Model
{
    protected $table = 'inv_numbering_series';

    public const DOCUMENT_TYPES = [
        'grn' => 'Goods Receipt (GRN)', 'expected_receipt' => 'Expected Receipt / ASN',
        'supplier_return' => 'Supplier Return', 'transfer' => 'Warehouse Transfer',
        'reservation' => 'Stock Reservation', 'pick_list' => 'Pick List', 'package' => 'Package',
        'dispatch' => 'Dispatch', 'customer_return' => 'Customer Return',
        'adjustment' => 'Stock Adjustment', 'count_sheet' => 'Count Sheet',
        'quotation' => 'Quotation', 'sales_order' => 'Sales Order', 'contract' => 'Contract',
    ];

    protected $fillable = ['company_id', 'document_type', 'prefix', 'next_number', 'padding_digits', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * Atomically reserves the next number for a document type. If no
     * series is configured, falls back to the document's historical
     * "<PREFIX>-<year>-<00001>" format via a plain count, so behavior is
     * unchanged until an admin opts in by configuring a series here.
     */
    public static function next(int $companyId, string $documentType, string $fallbackPrefix, callable $fallbackCounter): string
    {
        return DB::transaction(function () use ($companyId, $documentType, $fallbackPrefix, $fallbackCounter) {
            $series = self::where('company_id', $companyId)->where('document_type', $documentType)->where('is_active', true)->lockForUpdate()->first();

            if (! $series) {
                return $fallbackCounter();
            }

            $number = $series->next_number;
            $series->increment('next_number');

            return $series->prefix.'-'.str_pad((string) $number, $series->padding_digits, '0', STR_PAD_LEFT);
        });
    }
}
