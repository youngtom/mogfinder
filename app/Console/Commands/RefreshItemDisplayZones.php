<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Zone;

class RefreshItemDisplayZones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:display:refresh-zones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $zones = Zone::all();
	    $bar = $this->output->createProgressBar(count($zones));
	    
	    foreach ($zones as $zone) {
			$zone->updateDisplays();			
			$bar->advance();
	    }
	    
	    $bar->finish();
    }
}
