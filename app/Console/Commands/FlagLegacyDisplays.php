<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;
use App\ItemDisplay;
use DB;

class FlagLegacyDisplays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:displays:flag-legacy';

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
	    
	    ItemDisplay::update(['legacy' => 0]);
	    
        $sourceItems = Item::whereIn('id', function ($query) {
		    $query->select('item_id')->from('item_sources')->where('item_source_type_id', '=', 17);
	    })->where('transmoggable', '=', 1)->orderBy('bnet_id', 'ASC')->get();
	    
	    $itemIDs = $sourceItems->lists('id')->toArray();
	    
	    $displayIDs = $sourceItems->lists('item_display_id')->toArray();
	    $allItemIDs = Item::whereIn('item_display_id', $displayIDs)->get()->lists('id')->toArray();
	    $ignoreItemIDs = array_diff($allItemIDs, $itemIDs);
	    $ingoreItemDisplayIDs = Item::whereIn('id', $ignoreItemIDs)->get()->lists('item_display_id')->toArray();
	    $displayIDs = array_diff($displayIDs, $ingoreItemDisplayIDs);
	    
	    ItemDisplay::whereIn('id', $displayIDs)->update(['legacy' => 1]);
    }
}
