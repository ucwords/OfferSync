<?php

namespace App\Console;

use App\Console\Commands\OfferSync;
use App\Console\Commands\zhang;
use App\Console\Commands\Test;
use App\Console\Commands\AutoSync;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Filesystem\Filesystem;
use DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        OfferSync::class,
        Test::class,
        zhang::class,
        AutoSync::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        $schedule->call(function () {
            $file = new Filesystem;
            $file->cleanDirectory('storage/app');
        })->daily();


        $schedule->command('auto:sync bothads')->cron('*/15 * * * * *')->runInBackground()->sendOutputTo(storage_path('logs/bothads.log'))->withoutOverlapping();
        $schedule->command('auto:sync mobimelon')->cron('*/15 * * * * *')->runInBackground()->sendOutputTo(storage_path('logs/mobimelon.log'))->withoutOverlapping();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
