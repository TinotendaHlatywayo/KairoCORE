<?php

namespace Modules\SaaS\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An editable default message used by the billing notification schedule.
 * The super admin can change the subject, body and delivery channels.
 */
class PlatformBillingMessageTemplate extends Model
{
    protected $table = 'platform_billing_message_templates';

    protected $fillable = [
        'key',
        'name',
        'subject',
        'body',
        'send_platform_message',
        'send_email',
        'is_active',
    ];

    protected $casts = [
        'send_platform_message' => 'boolean',
        'send_email' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Replace {placeholder} tokens in a string with the supplied values.
     *
     * @param  array<string, scalar|null>  $values
     */
    public function render(string $text, array $values): string
    {
        foreach ($values as $key => $value) {
            $text = str_replace('{'.$key.'}', (string) ($value ?? ''), $text);
        }

        return $text;
    }

    /**
     * @return array<string, scalar|null> same keys, both subject and body applied
     */
    public function renderSubject(array $values): string
    {
        return $this->render((string) $this->subject, $values);
    }

    public function renderBody(array $values): string
    {
        return $this->render((string) $this->body, $values);
    }
}