<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\ItemDisplay;
use DB;

class UpdateItemDisplayRestrictions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:display:update-restrictions';

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
        DB::disableQueryLog();
	    ini_set('memory_limit','1024M');
	    ini_set('max_exeuction_time',0);
	    
        $itemDisplays = ItemDisplay::all();
	    $bar = $this->output->createProgressBar(count($itemDisplays));
	    
	    foreach ($itemDisplays as $itemDisplay) {
			$itemDisplay->updateRestrictions();
			
			$bar->advance();
	    }
	    
	    $bar->finish();
    }
}
