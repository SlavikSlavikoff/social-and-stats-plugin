<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourtTemplate extends Model
{
    protected $table = 'socialprofile_court_templates';

    protected $fillable = [
        'key',
        'name',
        'base_comment',
        'payload',
        'limits',
        'default_executor',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'limits' => 'array',
        'is_active' => 'boolean',
    ];

    public function cases(): HasMany
    {
        return $this->hasMany(CourtCase::class, 'template_id');
    }
}
