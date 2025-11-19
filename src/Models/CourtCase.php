<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CourtCase extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_AWAITING_REVERT = 'awaiting_revert';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REVOKED = 'revoked';

    protected $table = 'socialprofile_court_cases';

    protected $fillable = [
        'case_number',
        'user_id',
        'judge_id',
        'template_id',
        'mode',
        'executor',
        'status',
        'visibility',
        'continued_case_id',
        'issued_at',
        'expires_at',
        'finalized_at',
        'comment',
        'internal_notes',
        'payload',
        'metrics_applied',
        'created_by_executor_id',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'finalized_at' => 'datetime',
        'payload' => 'array',
        'metrics_applied' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (CourtCase $case): void {
            if (! $case->case_number) {
                $case->case_number = 'CASE-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
            }

            if (! $case->visibility) {
                $case->visibility = config('socialprofile.court.default_visibility', 'judges');
            }
        });
    }

    public function scopeVisibleFor(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query->where('visibility', 'public');
        }

        if ($user->can('social.court.judge')) {
            return $query;
        }

        if ($user->can('social.court.archive')) {
            return $query->whereIn('visibility', ['public', 'judges']);
        }

        return $query->where('visibility', 'public');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CourtTemplate::class, 'template_id');
    }

    public function continuedCase(): BelongsTo
    {
        return $this->belongsTo(self::class, 'continued_case_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(CourtAction::class, 'case_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CourtLog::class, 'case_id')->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CourtAttachment::class, 'case_id');
    }

    public function markStatus(string $status): void
    {
        $this->status = $status;
        $this->save();
    }
}
