<?php

namespace App\Console\Commands\wowhead;

use Illuminate\Console\Command;
use App\Item;
use App\ItemSource;

class ImportSourceData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wowhead:items:import-sources {type}';

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
        $type = $this->argument('type');
        
        $bar = $this->output->createProgressBar(count($items));
	    
	    foreach ($items as $item) {
		    $worldDropSources = ItemSource::where('item_id', '=', $item->id)->where('item_source_type_id', '=', 3)->get();
		    
		    if (!$worldDropSources->count()) {
			    $item->importWowheadSources([$type]);
		    }
		    $bar->advance();
	    }
	    
	    $bar->finish();
    }
}
