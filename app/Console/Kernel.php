<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     * ここにartisanコマンドクラスを記述する
     * @var array
     */
	protected $commands = [
		\App\Console\Commands\TestBatch::class,
		\App\Console\Commands\HayaokiBatch::class,
	];

    /**
     * Define the application's command schedule.
     * artisanコマンドの実行スケジュールを記述する
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
	protected function schedule(Schedule $schedule)
	{
		$schedule->command('batch:test')->daily();
		$schedule->command('batch:hayaoki')->dailyAt('7:00');
	}

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
