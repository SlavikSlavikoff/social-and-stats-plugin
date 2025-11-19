<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Models\User;
use Azuriom\Plugin\InspiratoStats\Database\Factories\UserRatingValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRatingValue extends Model
{
    use HasFactory;

    protected $table = 'socialprofile_user_ratings';

    protected $fillable = [
        'rating_id',
        'user_id',
        'value',
        'meta',
    ];

    protected $casts = [
        'value' => 'integer',
        'meta' => 'array',
    ];

    public function rating(): BelongsTo
    {
        return $this->belongsTo(ProgressionRating::class, 'rating_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): UserRatingValueFactory
    {
        return UserRatingValueFactory::new();
    }
}
