<?php

namespace Modules\Recovery\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformBackup extends Model
{
    protected $table = 'platform_backups';

    protected $fillable = [
        'filename',
        'scope',
        'school_id',
        'notes',
        'size_bytes',
        'checksum',
        'disk',
        'is_verified',
        'status',
        'error_log',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'size_bytes' => 'integer',
    ];

    public function restoreLogs(): HasMany
    {
        return $this->hasMany(PlatformRestoreLog::class, 'backup_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
