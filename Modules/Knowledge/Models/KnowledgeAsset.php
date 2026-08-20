<?php

declare(strict_types=1);

namespace Modules\Knowledge\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Library\Models\LibraryAuthor;
use Modules\Library\Models\LibraryCategory;

class KnowledgeAsset extends Model
{
    use BelongsToTenant;

    protected $table = 'knowledge_assets';

    protected $fillable = [
        'school_id',
        'uploaded_by_id',
        'approved_by_id',
        'library_category_id',
        'knowledge_format_id',
        'title',
        'subtitle',
        'subtype',
        'abstract_description',
        'visibility',
        'isbn',
        'publisher',
        'publication_year',
        'language',
        'media_type',
        'file_path',
        'external_url',
        'cover_image_path',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LibraryCategory::class, 'library_category_id');
    }

    public function format(): BelongsTo
    {
        return $this->belongsTo(KnowledgeFormat::class, 'knowledge_format_id');
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(
            LibraryAuthor::class,
            'knowledge_asset_author',
            'knowledge_asset_id',
            'library_author_id'
        )->withTimestamps();
    }

    public function copies(): HasMany
    {
        return $this->hasMany(KnowledgeAssetCopy::class, 'knowledge_asset_id');
    }

    // RESOURCE QUANTITY COUNTERS (DEDUCTIVE FOR DIGITAL)
    public function getTotalCopiesCount(): int
    {
        if ($this->media_type === 'digital') {
            return 1;
        }

        return $this->copies()->count();
    }

    public function getAvailableCopiesCount(): int
    {
        if ($this->media_type === 'digital') {
            return 1;
        }

        return $this->copies()->where('status', 'available')->count();
    }

    public function getDamagedCopiesCount(): int
    {
        if ($this->media_type === 'digital') {
            return 0;
        }

        return $this->copies()->where('condition', 'damaged')->count();
    }

    public function getLostCopiesCount(): int
    {
        if ($this->media_type === 'digital') {
            return 0;
        }

        return $this->copies()->where('status', 'lost')->count();
    }
}
