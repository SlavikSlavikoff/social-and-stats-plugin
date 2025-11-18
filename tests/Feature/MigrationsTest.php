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
