<?php

namespace Modules\SaaS\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Idempotency ledger for billing notifications. A unique index on
 * (school_id, template_key, billing_date) guarantees a given reminder is sent
 * at most once per tenant per cycle even if the scheduler runs repeatedly.
 */
class PlatformBillingNotificationLog extends Model
{
    protected $table = 'platform_billing_notification_logs';

    protected $fillable = [
        'school_id',
        'saas_subscription_id',
        'template_key',
        'billing_date',
        'channel',
        'subject',
        'recipient_count',
        'sent_at',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'sent_at' => 'datetime',
        'recipient_count' => 'integer',
    ];
}