<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\CallControllerFunction::class,
        \App\Console\Commands\TaskEmailer::class,
    ];
    
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('call:function')->dailyAt('9:00');
        $schedule->command('mail:function')->everyMinute();
    }

}
