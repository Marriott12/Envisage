<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAutomationRule;
use App\Models\AutomationExecution;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledAutomations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled automation rule executions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Processing scheduled automation executions...');

        // Get pending executions that are due
        $executions = AutomationExecution::with(['rule', 'user'])
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->limit(100)
            ->get();

        if ($executions->isEmpty()) {
            $this->info('No pending automation executions found.');
            return 0;
        }

        $this->info("Found {$executions->count()} pending execution(s).");

        $processed = 0;

        foreach ($executions as $execution) {
            try {
                // Dispatch job to process automation
                ProcessAutomationRule::dispatch($execution);

                $processed++;

                $this->line("✓ Dispatched execution #{$execution->id} for rule '{$execution->rule->name}'");

            } catch (\Exception $e) {
                $this->error("✗ Failed to dispatch execution #{$execution->id}: {$e->getMessage()}");

                Log::error('Failed to dispatch automation execution', [
                    'execution_id' => $execution->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Successfully dispatched {$processed} automation execution(s).");

        return 0;
    }
}
