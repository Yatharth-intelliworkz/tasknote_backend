<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\API\CronJobController;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class TaskEmailer extends Command
{
    protected $signature = 'mail:function';
    protected $description = 'Calls the function in the controller';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $controller = new CronJobController();
            $controller->taskMailSend();
            Log::info('Command Task Mailer successfully.');
        } catch (\Exception $e) {
            Log::error('Command failed: ' . $e->getMessage());
        }
        return 0;
    }
}
