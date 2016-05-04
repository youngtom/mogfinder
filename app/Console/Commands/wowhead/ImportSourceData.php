<?php

namespace App\Console\Commands\wowhead;

use Illuminate\Console\Command;
use App\Item;

class ImportSourceData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wowhead:items:import-sources';

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
        $items = Item::where('transmoggable', '=', 1)->get();
        
        $bar = $this->output->createProgressBar(count($items));
	    
	    foreach ($items as $item) {
		    $item->importWowheadSources();
		    $bar->advance();
	    }
	    
	    $bar->finish();
    }
}
