<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;
use App\ItemDisplay;
use DB;

class RemoveDuplicateItemDisplays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:displays:remove-duplicates';

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
	    
	    $results = DB::table('item_displays')->groupBy('bnet_display_id', 'item_subtype_id', 'inventory_type_id')->havingRaw('count(*) > 1')->get();
	    
	    foreach ($results as $res) {
			$displays = ItemDisplay::where('bnet_display_id', '=', $res->bnet_display_id)->where('item_subtype_id', '=', $res->item_subtype_id)->where('inventory_type_id', '=', $res->inventory_type_id)->get();
			
			$count = 0;
			$keepDisplay = null;
			foreach ($displays as $display) {
				if (++$count == 1) {
					$keepDisplay = $display;
				} else {
					Item::where('item_display_id', '=', $display->id)->update(['item_display_id' => $keepDisplay->id]);
					$this->line('Removing Display: ' . $display->id . ' - replaced with ID: ' . $keepDisplay->id);
					$display->delete();
				}
			}
	    }
    }
}
