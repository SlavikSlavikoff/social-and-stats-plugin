<?php

namespace Azuriom\Plugin\InspiratoStats\Tests\Feature;

use Azuriom\Plugin\InspiratoStats\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class MigrationsTest extends TestCase
{
    public function test_all_plugin_tables_are_created(): void
    {
        $tables = [
            'socialprofile_social_scores' => ['user_id', 'score', 'created_at', 'updated_at'],
            'socialprofile_activity_points' => ['user_id', 'points'],
            'socialprofile_coin_balances' => ['user_id', 'balance', 'hold'],
            'socialprofile_game_statistics' => ['user_id', 'played_minutes', 'kills', 'deaths', 'extra_metrics'],
            'socialprofile_trust_levels' => ['user_id', 'level', 'granted_by', 'note', 'deleted_at'],
            'socialprofile_violations' => ['user_id', 'type', 'reason', 'points', 'issued_by', 'evidence_url', 'deleted_at'],
            'socialprofile_api_tokens' => ['name', 'token_hash', 'scopes', 'allowed_ips', 'rate_limit', 'created_by'],
            'socialprofile_timelines' => ['type', 'slug', 'title', 'is_active', 'show_period_labels'],
            'socialprofile_timeline_periods' => ['timeline_id', 'title', 'start_date', 'end_date', 'position'],
            'socialprofile_timeline_cards' => ['timeline_id', 'period_id', 'title', 'items', 'is_visible'],
            'socialprofile_automation_integrations' => ['name', 'type', 'config', 'is_default'],
            'socialprofile_automation_rules' => ['name', 'trigger_type', 'conditions', 'actions'],
            'socialprofile_automation_logs' => ['trigger_type', 'status', 'payload', 'actions'],
            'socialprofile_ratings' => ['slug', 'name', 'type', 'scale_min', 'scale_max'],
            'socialprofile_user_ratings' => ['user_id', 'rating_id', 'value'],
            'socialprofile_rating_thresholds' => ['rating_id', 'value', 'label', 'direction', 'is_punishment', 'band_min', 'band_max'],
            'socialprofile_rating_threshold_actions' => ['threshold_id', 'action', 'config'],
            'socialprofile_rating_rules' => ['rating_id', 'name', 'trigger_key', 'delta'],
            'socialprofile_rating_events' => ['rating_id', 'user_id', 'amount'],
            'socialprofile_rating_user_thresholds' => ['threshold_id', 'user_id', 'direction', 'reached_at'],
        ];

        foreach ($tables as $table => $columns) {
            $this->assertTrue(
                Schema::hasTable($table),
                sprintf('Failed asserting that table %s exists.', $table)
            );

            foreach ($columns as $column) {
                $this->assertTrue(
                    Schema::hasColumn($table, $column),
                    sprintf('Failed asserting that column %s exists on table %s.', $column, $table)
                );
            }
        }
    }
}
