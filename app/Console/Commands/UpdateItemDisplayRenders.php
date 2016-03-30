<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\ItemDisplay;

class UpdateItemDisplayRenders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:display:update-renders';

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
	    $itemDisplays = ItemDisplay::where('render_image_id', '=', null)->where('transmoggable', '=', 1)->get();
	    $bar = $this->output->createProgressBar(count($itemDisplays));
	    
	    foreach ($itemDisplays as $itemDisplay) {
			$file = $itemDisplay->downloadRenderFile();
			
			if (!$file) {
				$this->error('Error finding render for displayID: ' . $itemDisplay->id);
			}
			
			$bar->advance();
	    }
	    
	    $bar->finish();
    }
}
