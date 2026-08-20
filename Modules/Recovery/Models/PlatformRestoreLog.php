<?php

namespace Modules\Recovery\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformRestoreLog extends Model
{
    protected $table = 'platform_restore_logs';

    protected $fillable = [
        'backup_id',
        'performed_by_id',
        'status',
        'error_details',
    ];

    public function backup(): BelongsTo
    {
        return $this->belongsTo(PlatformBackup::class, 'backup_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }
}
