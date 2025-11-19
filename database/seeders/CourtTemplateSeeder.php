<?php

namespace Azuriom\Plugin\InspiratoStats\Database\Seeders;

use Azuriom\Plugin\InspiratoStats\Models\CourtTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class CourtTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = config('socialprofile.court.templates', []);

        foreach ($templates as $template) {
            $model = CourtTemplate::firstOrNew(['key' => $template['key']]);

            $model->fill([
                'name' => $template['name'],
                'base_comment' => $template['base_comment'] ?? null,
                'payload' => Arr::get($template, 'payload', []),
                'limits' => Arr::get($template, 'limits'),
                'default_executor' => Arr::get($template, 'default_executor', config('socialprofile.court.default_executor', 'site')),
            ]);

            if (! $model->exists) {
                $model->is_active = true;
            }

            $model->save();
        }
    }
}
