<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class OrderCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:order-state';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'to change order state into delivered after 24 hours';

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
     * @return int
     */
    public function handle()
    {
        Order::where('request_state_id', 4)->update(['request_state_id' => 5]);
        \Log::info("Cron is working fine!");
        // return 0;
    }
}
