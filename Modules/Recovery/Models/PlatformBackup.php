<?php

namespace Modules\Recovery\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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

    /**
     * The backup manager passes rows through toArray(), which serialises
     * timestamps to UTC ISO-8601. Re-parse that value and convert it back to
     * the configured app timezone so the UI never shows a 2h offset.
     */
    public static function formatTimestamp(?string $value, string $format = 'd M Y, H:i'): string
    {
        if (blank($value)) {
            return '';
        }

        return Carbon::parse($value)->timezone(config('app.timezone'))->format($format);
    }
}
