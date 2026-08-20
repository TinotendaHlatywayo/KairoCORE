<?php

namespace Modules\CMS\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CmsMedia extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'uuid',
        'filename',
        'original_filename',
        'mime_type',
        'extension',
        'file_size',
        'disk',
        'path',
        'url',
        'width',
        'height',
        'dominant_color',
        'exif',
        'folder',
        'tags',
        'alt_text',
        'caption',
        'credit',
        'usage_count',
        'used_in',
        'variants',
    ];

    protected $casts = [
        'exif' => 'array',
        'tags' => 'array',
        'used_in' => 'array',
        'variants' => 'array',
    ];

    public function getThumbnailUrl(): string
    {
        return $this->variants['thumbnail']['url'] ?? $this->url;
    }

    public function getResponsiveUrls(): array
    {
        return $this->variants ?? [];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function isDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
        ]);
    }
}
