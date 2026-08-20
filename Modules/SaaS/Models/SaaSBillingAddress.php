<?php

namespace Modules\SaaS\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaaSBillingAddress extends Model
{
    protected $table = 'saas_billing_addresses';

    protected $fillable = [
        'school_id', 'company_name', 'vat_number', 'email_address',
        'phone_number', 'address_line_1', 'address_line_2', 'city',
        'state_province', 'postal_code', 'country',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
