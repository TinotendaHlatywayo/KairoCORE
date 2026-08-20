<?php

namespace Modules\Admin\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Enums\EmailCategory;

class EmailConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'category',
        'mailer',
        'from_name',
        'from_email',
        'reply_to_name',
        'reply_to_email',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'is_enabled',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'is_enabled' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    protected $hidden = ['password'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function category(): EmailCategory
    {
        return EmailCategory::from($this->category);
    }

    public function scopeCategory($query, EmailCategory|string $category)
    {
        return $query->where('category', $category instanceof EmailCategory ? $category->value : $category);
    }

    public function scopeForSchool($query, int|School $school)
    {
        return $query->where('school_id', is_int($school) ? $school : $school->id);
    }

    public function usesPlatformMailer(): bool
    {
        return $this->mailer === 'platform';
    }

    public function usesSmtp(): bool
    {
        return $this->mailer === 'smtp';
    }

    /**
     * Whether this configuration is ready to send email.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->from_email) && $this->is_enabled;
    }

    public function isUsable(): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        if ($this->usesPlatformMailer()) {
            return ! $this->isPlatformSender();
        }

        if ($this->usesSmtp()) {
            return ! empty($this->host) && ! empty($this->username) && ! empty($this->password);
        }

        return true;
    }

    /**
     * True when the configured sender identity collides with the platform-level account.
     */
    public function isPlatformSender(): bool
    {
        return isPlatformEmail($this->from_email);
    }
}
