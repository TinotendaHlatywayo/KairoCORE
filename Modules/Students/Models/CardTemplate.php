<?php

namespace Modules\Students\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CardTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'name',
        'orientation',
        'barcode_format',
        'background_path',
        'layout_config',
        'is_active',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($template) {
            // ENFORCE GLOBAL SINGLE ACTIVE STATE
            if ($template->is_active) {
                static::where('school_id', $template->school_id)
                    ->where('id', '!=', $template->id)
                    ->update(['is_active' => false]);
            }
        });
    }
}
