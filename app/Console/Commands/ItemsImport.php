<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use App\Item;
use App\StoredVariable;

class ItemsImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:import {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import battle.net items';

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
	    
	    if ($id = $this->argument('id')) {
		    $itemID = $maxID = $id;
	    } else {
			$lastIDVar = StoredVariable::where('var', '=', 'latest_imported_item')->first();
			$lastID = $lastIDVar->val;
			$maxID = Config::get('settings.bnet_max_item_id');
			
			$itemID = $lastID + 1;   
	    }
		
		$items = [];
		
		while ($itemID <= $maxID) {
			if ($_items = Item::importBnetData($itemID)) {		
				$this->line('ItemID: ' . $itemID . ':');
				$items = array_merge($items, $_items);
				
				foreach ($_items as $_item) {
					$this->line(' - Imported: ' . $_item->name);
					
					if ($_item->context) {
						$this->line(' - Context: ' . $_item->context->label . ' ' . $i_tem->bonus);
					}
				}
			}
			
			if ($lastIDVar) {
				$lastIDVar->val = $itemID;
				$lastIDVar->save();
				$itemID++;
			}
		}
		$this->info(count($items) . ' items imported.');
    }
}
