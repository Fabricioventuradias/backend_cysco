<?php

namespace App\Jobs;

use App\Http\Controllers\CronJob\PerDayCronController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunPerDayCronJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200; // 20 min ceiling
    public $tries = 1;      // don't auto-retry a partially-completed daily run

    public function handle(PerDayCronController $controller)
    {
        $controller->runDailyTasks();
    }

    public function failed(\Throwable $exception)
    {
        \Log::channel('per_day_cron_log')->emergency([
            'message' => 'RunPerDayCronJob FAILED: ' . $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}