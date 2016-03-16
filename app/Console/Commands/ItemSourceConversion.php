<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;
use App\ItemSource;
use App\ItemSourceType;
use DB;

class ItemSourceConversion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:source-field-conversion';

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
	    
	    $items = Item::where('item_display_id', '>', 0)->where('transmoggable', '=', 1)->get();
	    
	    $items->each(function ($item) {
		   if ($item->item_source_id) {
			   $source = ItemSource::find($item->item_source_id);
			   
			   if ($source) {
				   $newSource = new ItemSource;
				   $newSource->item_id = $item->id;
				   $newSource->bnet_source_id = $source->bnet_source_id;
				   $newSource->item_source_type_id = $source->item_source_type_id;
				   $newSource->import_source = 'bnet';
				   $newSource->save();
				   
				   $this->info('Item bnet source added: ' . $item->id);
			   }
			   
			   $item->item_source_id = null;
			} else {
				$newSource = false;
			}
			
			if ($item->wowdb_source_id && $item->wowdb_source_type) {
			   $sourceType = ItemSourceType::where('wowdb_item_type', '=', $item->wowdb_source_type)->first();
			   
			   if ($sourceType) {
				   if (!$newSource || !($sourceType->id == $newSource->item_source_type_id && $item->wowdb_source_id == $newSource->bnet_source_id)) {
					   $wowdbSource = new ItemSource;
					   $wowdbSource->item_id = $item->id;
					   $wowdbSource->bnet_source_id = $item->wowdb_source_id;
					   $wowdbSource->item_source_type_id = $sourceType->id;
					   $wowdbSource->import_source = 'wowdb';
					   $wowdbSource->save();
					   
					   $this->info('Item wowdb source added: ' . $item->id);
				   }
				   
				   $item->wowdb_source_id = null;
				   $item->wowdb_source_type = null;
			   } else {
				   $this->error('Wowdb source type ' . $item->wowdb_source_type . ' not found.');
			   }
		   }
			   
			$item->save(); 
	    });
    }
}
