<?php

namespace Modules\Academics\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'name', 'code', 'level', 'teacher_id', 'workflow_status', 'workflow_completed_at', 'sequence_order', 'next_level_id', 'is_terminal'];

    protected $casts = [
        'workflow_completed_at' => 'datetime',
        'is_terminal' => 'boolean',
    ];

    public function nextLevel(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'next_level_id');
    }

    public function previousLevel(): HasMany
    {
        return $this->hasMany(__CLASS__, 'next_level_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }
}
