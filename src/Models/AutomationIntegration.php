<?php

namespace Azuriom\Plugin\InspiratoStats\Models;

use Azuriom\Plugin\InspiratoStats\Database\Factories\AutomationIntegrationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<string, mixed>|null $config
 */
class AutomationIntegration extends Model
{
    use HasFactory;

    public const TYPE_RCON = 'minecraft_rcon';
    public const TYPE_DATABASE = 'minecraft_db';
    public const TYPE_SOCIAL_BOT = 'social_bot';

    protected $table = 'socialprofile_automation_integrations';

    protected $fillable = [
        'name',
        'type',
        'config',
        'is_default',
        'description',
    ];

    protected $casts = [
        'config' => 'array',
        'is_default' => 'boolean',
    ];

    /**
     * @return Builder<Model>
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function configValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config ?? [], $key, $default);
    }

    protected static function newFactory(): AutomationIntegrationFactory
    {
        return AutomationIntegrationFactory::new();
    }
}
