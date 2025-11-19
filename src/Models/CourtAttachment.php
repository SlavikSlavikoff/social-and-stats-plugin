<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtAttachment extends Model
{
    protected $table = 'socialprofile_court_attachments';

    protected $fillable = [
        'case_id',
        'type',
        'path',
        'description',
    ];

    public function courtCase(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }
}
