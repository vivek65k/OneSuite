<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'event', 'via_email', 'via_sms', 'via_whatsapp', 'via_in_app',
        'email_template_id', 'sms_template_id', 'whatsapp_template_id', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'via_email' => 'boolean',
            'via_sms' => 'boolean',
            'via_whatsapp' => 'boolean',
            'via_in_app' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function smsTemplate(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class);
    }

    public function whatsappTemplate(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class);
    }
}
