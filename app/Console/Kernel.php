<?php

namespace App\Console;

use App\Console\Commands\ImporterCommand;
use App\Console\Commands\SapImportCommand;
use App\Console\Commands\StockAlertCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SapImportCommand::class,
        ImporterCommand::class,
        StockAlertCommand::class
    ];

    /**
     * scheduleTimezone
     *
     * @return void
     */
    protected function scheduleTimezone()
    {
        return 'Asia/Jakarta';
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('queue:work --queue=emails,default --stop-when-empty')->everyMinute()->withoutOverlapping();
        $schedule->command('sap:import')->everyTwoHours()->withoutOverlapping();
        $schedule->command('alert:stock')->dailyAt('23:00')->withoutOverlapping();
    }
}
