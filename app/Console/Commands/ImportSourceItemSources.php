<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;
use App\ItemSource;

class ImportSourceItemSources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:sources:import-source-item-sources';

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
        $itemIDs = ItemSource::whereNotNull('source_item_id')->groupBy('source_item_id')->get(['source_item_id'])->lists('source_item_id')->toArray();
        $items = Item::whereIn('id', $itemIDs)->get();
        
        $bar = $this->output->createProgressBar(count($items));
        
        foreach ($items as $item) {
	        if (!$item->itemSources->count()) {
		        $item->importWowheadSources();
		    }
	        $bar->advance();
        }
        
        $bar->finish();
    }
}
