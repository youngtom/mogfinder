<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Libraries\LuaParser;
use App\Item;

class GameItemsImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:game-import {filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports item data from WoW savedvariable file';

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
	    $filename = $this->argument('filename');
        if (!$filename || !file_exists($filename)) {
	        $this->error('Please specify a valid file');
	        return;
        }
        
        $parser = new LuaParser($filename);
        $data = $parser->toArray();
        
        foreach ($data['MCCISaved']['items'] as $itemID => $xmoggable) {
	        $items = Item::where('bnet_id', '=', $itemID)->get();
	        
	        if ($items && $items->count()) {
		        $items->each(function ($item) use ($xmoggable) {
			        if ($item->inventory_type_id) {
			    		$item->transmoggable = ($item->item_display_id && ($item->inventory_type_id == 4 || $item->inventory_type_id == 19 || ($xmoggable && $item->inventoryType->transmoggable))) ? 1 : $item->transmoggable; // prevent invalid game data from overwriting custom toggles (ie shirts/tabards)
			    	} else {
				    	$item->transmoggable = ($xmoggable && $item->item_display_id) ? 1 : $item->transmoggable;
				    	
				    	if ($xmoggable) {
					    	$this->error('Xmoggable Item ' . $item->bnet_id . ' has no inventory type set');
				    	}
			    	}
			        $item->imported_from_game = 1;
					$item->save(); 
					
					if ($xmoggable && $item->inventory_type_id && !$item->inventoryType->transmoggable) {
						$this->line('Item ' . $item->bnet_id . ' is not a valid xmog slot (' . $item->inventoryType->name . ')');
					}
		        });
		    } else {
			    $this->line('In-game item ' . $itemID . ' (' . $xmoggable . ') not found.');
		    }
        }
    }
}
