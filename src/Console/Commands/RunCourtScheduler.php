<?php

namespace Azuriom\Plugin\InspiratoStats\Console\Commands;

use Azuriom\Plugin\InspiratoStats\Support\Court\CourtScheduler;
use Illuminate\Console\Command;

class RunCourtScheduler extends Command
{
    protected $signature = 'socialprofile:court:tick';

    protected $description = 'Process court revert jobs and webhook deliveries.';

    public function handle(CourtScheduler $scheduler): int
    {
        $scheduler->tick();

        $this->info('Court scheduler tick executed.');

        return Command::SUCCESS;
    }
}
