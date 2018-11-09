<?php

namespace App\Console;

use App\Console\Commands\OfferSync;
use App\Console\Commands\zhang;
use App\Console\Commands\Test;
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

        //用户测试
        /*$schedule->call(function () {
            DB::table('test')->insert(['content' => '7878']);
        })->everyMinute();*/

        //$schedule->command('offer:sync inplayable')->cron('*/5 * * * * *')->runInBackground()->sendOutputTo(storage_path('logs/inplayable.log'))->withoutOverlapping();
        $schedule->command('offer:sync inplayable')->everyFiveMinutes()->runInBackground()->sendOutputTo(storage_path('logs/inplayable.log'))->withoutOverlapping();
        //$schedule->command('offer:sync pai')->hourly()->runInBackground()->sendOutputTo(storage_path('logs/pai.log'))->withoutOverlapping();
        //$schedule->command('offer:sync avatarmobi')->everyThirtyMinutes()->runInBackground()->sendOutputTo(storage_path('logs/avatarmobi.log'))->withoutOverlapping();
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
