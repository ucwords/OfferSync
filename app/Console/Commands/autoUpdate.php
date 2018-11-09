<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class autoUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 's2s这边自动更新';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        
    }
}
