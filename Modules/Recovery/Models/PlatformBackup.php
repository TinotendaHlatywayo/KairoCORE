<?php

namespace Modules\Recovery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformBackup extends Model
{
    protected $table = 'platform_backups';

    protected $fillable = [
        'filename',
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
}
