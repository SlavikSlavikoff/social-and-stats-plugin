<?php

namespace Azuriom\Plugin\InspiratoStats\Console\Commands;

use Azuriom\Plugin\InspiratoStats\Support\Automation\AutomationService;
use Illuminate\Console\Command;

class RunAutomationScheduler extends Command
{
    protected $signature = 'socialprofile:automation:tick';

    protected $description = 'Запуск планировщика автоматизации (ежемесячные награды и проверки).';

    public function handle(AutomationService $service): int
    {
        $result = $service->runMonthlyScheduler();

        $this->info(sprintf('Automation scheduler status: %s', $result['status'] ?? 'unknown'));

        return self::SUCCESS;
    }
}
