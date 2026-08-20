<?php

namespace Modules\Communication\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampusResource extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'communication_resources';

    protected $fillable = [
        'school_id',
        'title',
        'description',
        'thumbnail_path',
        'file_path',
        'category',
        'visibility',
        'version',
        'tags',
        'download_count',
    ];

    protected $casts = [
        'visibility' => 'array',
        'tags' => 'array',
        'download_count' => 'integer',
    ];
}
