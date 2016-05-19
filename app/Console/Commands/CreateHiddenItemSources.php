<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;
use App\ItemSource;

class CreateHiddenItemSources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:sources:create-hidden-sources';

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
        $itemSources = ItemSource::whereNotNull('source_item_id')->get();
        
        foreach ($itemSources as $itemSource) {
	        $itemSourceDropSources = $itemSource->sourceItem->itemSources()->whereIn('item_source_type_id', [4, 15, 21])->get();
	        
	        foreach ($itemSourceDropSources as $source) {
				$newSource = ItemSource::where('item_id', '=', $itemSource->item_id)->where('item_source_type_id', '=', $source->item_source_type_id)->where('bnet_source_id', '=', $source->bnet_source_id)->where('boss_id', '=', $source->boss_id)->where('zone_id', '=', $source->zone_id)->first();
				
				if (!$newSource) {
					$newSource = new ItemSource;
					$newSource->item_id = $itemSource->item_id;
					$newSource->item_source_type_id = $source->item_source_type_id;
					$newSource->bnet_source_id = $source->bnet_source_id;
					$newSource->boss_id = $source->boss_id;
					$newSource->zone_id = $source->zone_id;
					$newSource->hidden = 1;
					$newSource->import_source = 'hiddenSourceImport';
					$newSource->save();
					$this->line('New source created for item: ' . $newSource->item_id . '(Type: ' . $newSource->item_source_type_id . ' - bnetID: ' . $newSource->bnet_source_id . ')');
				} else {
					$this->line('Source already exists for item: ' . $newSource->item_id . '(Type: ' . $newSource->item_source_type_id . ' - bnetID: ' . $newSource->bnet_source_id . ')');
				}
	        }
        }
    }
}
