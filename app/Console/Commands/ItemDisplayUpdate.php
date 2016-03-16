<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;
use App\ItemDisplay;
use App\ItemSubtype;
use App\InventoryType;
use App\Mogslot;
use DB;

class ItemDisplayUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:display:update-mogslots';

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
	    
	    $itemDisplays = ItemDisplay::where('mogslot_id', '=', null)->where('transmoggable', '=', 1)->get();
	    $bar = $this->output->createProgressBar(count($itemDisplays));
	    
	    $itemDisplays->each(function($itemDisplay) use ($bar) {
		    //classify misc helms and chests as cosmetic
		    if ($itemDisplay->item_subtype_id == 52 && ($itemDisplay->inventory_type_id == 1 || $itemDisplay->inventory_type_id == 5)) {
			    $itemSubtypeID = 57;
		    } else {
			    $itemSubtypeID = $itemDisplay->item_subtype_id;
		    }
		    
			$mogslot = Mogslot::where('inventory_type_id', '=', $itemDisplay->inventory_type_id)->where('item_subtype_id', '=', $itemSubtypeID)->first();
			
			if (!$mogslot) {
				$mogslot = new Mogslot;
				$mogslot->inventory_type_id = $itemDisplay->inventory_type_id;
				$mogslot->item_subtype_id = $itemSubtypeID;
				
				$inventoryType = InventoryType::find($mogslot->inventory_type_id);
				$itemSubtype = ItemSubtype::find($mogslot->item_subtype_id);
				
				if ($itemSubtype->item_type_id == 3) {
					$mogslot->label = $itemSubtype->name_full;
				} else {
					$mogslot->label = trim($itemSubtype->name_full . ' ' . $inventoryType->name);
				}
				
				if (!$mogslot->label) {
					$this->error('Problem with displayID: ' . $itemDisplay->id . ' - InvTypeID: ' . $mogslot->inventory_type_id . ' - SubtypeID: ' . $mogslot->item_subtype_id);
					die;
				}
				
				$mogslot->url_token = str_slug($mogslot->label);
				$mogslot->allowed_class_bitmask = $itemSubtype->usable_by_classes_bitmask;
				$mogslot->save();
			}
			
			$itemDisplay->mogslot_id = $mogslot->id;
			$itemDisplay->save();
			
			$bar->advance();
	    });
	    
	    $bar->finish();
    }
}
