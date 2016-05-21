<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;
use App\ItemSource;

class ImportLegacyQuestSources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:sources:import-legacy-quests';

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
	    $sourceData = file(storage_path() . '/app/imports/sourcedata_new.txt');
	    
		$sourceByItem = [];
		$lineCount = 0;
		
	    foreach ($sourceData as $str) {
			preg_match_all('/\[(")?(?P<itemid>[0-9:]+)(")?\] \= \{(?P<sourceid>\d)([#,](?P<data>.+))?\},/', $str, $matches);
			
			if (count($matches)) {
				$itemID = @$matches['itemid'][0];
				$sourceID = @$matches['sourceid'][0];
				$data = @$matches['data'][0];
				
				if ($sourceID == 2) {
					if (strpos($itemID, ':')) {
						list($bnetID, $bonus) = explode(':', $itemID);
					} else {
						$bnetID = $itemID;
						$bonus = null;
					}
					
					if ($itemID && $sourceID && !$bonus) {
						$sourceByItem[$bnetID] = $data;
					} else {
						$this->line('Problem with line: ' . $str);
					}
				}
			}
		}
		
        $legacyItemIDs = ItemSource::where('item_source_type_id', '=', 17)->get(['item_id'])->lists('item_id')->toArray();
        
        $items = Item::whereIn('id', $legacyItemIDs)->where('transmoggable', '=', 1)->get();
        $bar = $this->output->createProgressBar($items->count());
        
        $questBnetIDs = [];
        
        foreach ($items as $item) {
	        $data = $item->getBnetData();
	        
	        if (@$data['itemSource'] && @$data['itemSource']['sourceId'] && @$data['itemSource']['sourceType'] == 'REWARD_FOR_QUEST') {
		        $source = new ItemSource;
			    $source->item_id = $item->id;
			    $source->bnet_source_id = $data['itemSource']['sourceId'];
			    $source->item_source_type_id = 7;
			    $source->import_source = 'ImportLegacyQuestsBnet';
			    $source->hidden = 1;
			    $source->save();
			    
			    $questBnetIDs[] = $data['itemSource']['sourceId'];
	        }
	        
	        $itemSourceStr = (array_key_exists($item->bnet_id, $sourceByItem)) ? $sourceByItem[$item->bnet_id] : false;
	        
	        if ($itemSourceStr) {
		        $sourceArr = explode(',', $itemSourceStr);
		        
		        foreach ($sourceArr as $sourceBnetID) {
					$source = ItemSource::where('item_id', '=', $item->id)->where('bnet_source_id', '=', $sourceBnetID)->where('item_source_type_id', '=', 7)->first();
					
					if (!$source) {
						$source = new ItemSource;
						$source->item_id = $item->id;
						$source->bnet_source_id = $sourceBnetID;
						$source->item_source_type_id = 7;
						$source->import_source = 'ImportLegacyQuestsMisc';
						$source->hidden = 1;
						$source->save();
						
						$questBnetIDs[] = $sourceBnetID;
					}
				}
	        }
	        
	        $bar->advance();
        }
        $bar->finish();
        
        $existingSources = ItemSource::whereIn('bnet_source_id', $questBnetIDs)->whereNotIn('item_id', $legacyItemIDs)->where('item_source_type_id', '=', 7)->get();
        
        foreach ($existingSources as $source) {
	        $this->line('EXISTING SOURCE: ' . $source->bnet_source_id . '(' . $source->id . ')');
        }
    }
}
