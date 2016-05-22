<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;
use App\ItemSource;
use App\WowheadCache;

class ImportLegacyQuestDataExternal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:sources:import-external-legacy-quests';

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
        $legacyItemIDs = ItemSource::where('item_source_type_id', '=', 17)->get(['item_id'])->lists('item_id')->toArray();
        
        $items = Item::whereIn('id', $legacyItemIDs)->where('transmoggable', '=', 1)->get();
        $bar = $this->output->createProgressBar($items->count());
        
        foreach($items as $item) {
	        $html = WowheadCache::getLegacyItemHtml($item->bnet_id);
	        
	        if ($html) {
				$sourceData = $this->_processWowheadHtml($html);
				
				if (!$sourceData || !count($sourceData)) {
					$this->info('Source data not found for bnet id: ' . $item->bnet_id);
				} else {
					foreach ($sourceData as $questArr) {
						$questID = $questArr['id'];
						
						$itemSource = ItemSource::where('item_source_type_id', '=', 7)->where('bnet_source_id', '=', $questID)->where('item_id', '=', $item->id)->first();
				    
					    if (!$itemSource) {
						    $itemSource = new ItemSource;
						    $itemSource->item_id = $item->id;
						    $itemSource->item_source_type_id = 7;
						    $itemSource->bnet_source_id = $questID;
						    $itemSource->hidden = 1;
						    $itemSource->import_source = 'openwowImport';
						    $this->line('Adding source for ' . $item->bnet_id);
					    } else {
						    $this->line('Source already exists for ' . $item->bnet_id . ' (' . $itemSource->import_source . ')');
					    }
					    
					    if (@$questArr['side'] && ($questArr['side'] == 1 || $questArr['side'] == 2)) {
						    $itemSource->faction_id = $questArr['side'];
					    }
					    $itemSource->save();
					}
				}
			}
			
			$bar->advance();
        }
        
        $bar->finish();
    }
    
    private function _processWowheadHtml($html) {
	    preg_match_all('/new Listview\(\{template\:\'quest\'(.+),data\:(?P<data>\[.+\])(.+)\)\;/', $html, $matches);
	    
	    if (@$matches['data'][0]) {
			$json = $matches['data'][0];
			$json = preg_replace('/(")?(id)(?(1)\1|)\:/', '"id":', $json);
			$json = preg_replace('/,(")?(level)(?(1)\1|)/', ',"level"', $json);
			$json = preg_replace('/,(")?(side)(?(1)\1|)/', ',"side"', $json);
			$json = preg_replace('/,(")?(reqlevel)(?(1)\1|)/', ',"reqlevel"', $json);
			$json = preg_replace('/,(")?(itemrewards)(?(1)\1|)/', ',"itemrewards"', $json);
			$json = preg_replace('/,(")?(money)(?(1)\1|)/', ',"money"', $json);
			$json = preg_replace('/,(")?(itemchoices)(?(1)\1|)/', ',"itemchoices"', $json);
			$json = preg_replace('/,(")?(xp)(?(1)\1|)/', ',"xp"', $json);
			$json = preg_replace('/,(")?(category)(?(1)\1|)\:(.+),/', ',', $json);
			$json = preg_replace('/,(")?(category2)(?(1)\1|)/', ',"category2"', $json);
			$json = preg_replace('/,(")?(type)(?(1)\1|)/', ',"type"', $json);
			$json = preg_replace('/,(")?(name)(?(1)\1|)\:(.+),/', ',', $json);
			$json = preg_replace('/(")?(undefined)(?(1)\1|)/', '"undefined"', $json);
			$json = str_replace("'", '"', $json);
			
			$jsonArr = json_decode($json, true);
			
			if (!$jsonArr) {
				$this->error('Malformed json for item: ' . $json);
				die;
			}
			
			return $jsonArr;
		} else {
			return false;
		}
	}
}
