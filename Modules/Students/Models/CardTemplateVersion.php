<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;

class CardTemplateVersion extends Model
{
    protected $fillable = [
        'card_template_id',
        'version_number',
        'layout_config',
        'created_by_id',
    ];

    protected $casts = [
        'layout_config' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(CardTemplate::class, 'card_template_id');
    }
}
