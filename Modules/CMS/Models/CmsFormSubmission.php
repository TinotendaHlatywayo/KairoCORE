<?php

namespace Modules\CMS\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsFormSubmission extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'cms_page_id',
        'form_handle',
        'form_data',
        'meta',
        'status',
        'notes',
        'assigned_to',
        'replied_at',
    ];

    protected $casts = [
        'form_data' => 'array',
        'meta' => 'array',
        'replied_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'cms_page_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function markAsRead(): void
    {
        $this->update(['status' => 'read']);
    }

    public function markAsReplied(): void
    {
        $this->update(['status' => 'replied', 'replied_at' => now()]);
    }

    public function markAsSpam(): void
    {
        $this->update(['status' => 'spam']);
    }
}
