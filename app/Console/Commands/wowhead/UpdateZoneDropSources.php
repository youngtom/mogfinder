<?php

namespace App\Console\Commands\wowhead;

use Illuminate\Console\Command;
use App\Item;
use App\ItemSource;
use App\Zone;
use App\Boss;

class UpdateZoneDropSources extends Command
{
	private static $client = null;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wowhead:items:update-zone-drop-data';

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
        $sources = ItemSource::where('item_source_type_id', '=', 15)->where('dynamic_quest_rewards', '=', 0)->get();
        
        foreach ($sources as $source) {
	        if ($source->item->transmoggable) {
		        $dataArr = $this->getWowheadSourceData($source->item->bnet_id);
		        
		        $this->info('Checking: ' . $source->item->bnet_id . ' - SourceID: ' . $source->id);
		        
		        if ($dataArr === false) {
			        $this->error('Invalid source data for: ' . $source->item->bnet_id);
		        } elseif ($dataArr === null) {
			        $this->error('Malformed json for : ' . $source->item->bnet_id);
		        } else {
			        if (count($dataArr) == 1) {
				        $data = $dataArr[0];
				        $zoneBnetID = $data['location'][0];
				        $npcID = $data['id'];
				        
				        if ($zoneBnetID != $source->zone->bnet_id) {
					        $zone = Zone::where('bnet_id', '=', $zoneBnetID)->first();
				        } else {
					        $zone = $source->zone;
				        }
				        
				        $convert = true;
				        
				        if (!$zone) {
					        $this->error('Invalid zone (' . $zoneBnetID . ') for: ' . $source->item->bnet_id);
					        $convert = false;
				        }
				        
				        if ($convert && ($zone->is_raid || $zone->is_dungeon)) { //verify npcid is a boss
					        $boss = Boss::where('bnet_id', '=', $npcID)->where('zone_id', '=', $zone->id)->first();
					        
					        if (!$boss) {
						        $this->error('Item is not a boss drop: ' . $source->item->bnet_id);
						        $convert = false;
					        }
				        }
				        
				        if ($convert) {
					        $source->item_source_type_id = 4;
					        $source->bnet_source_id = $npcID;
					        $source->zone_id = $zone->id;
					        $this->line('- Converting to boss drop');
					    }
			        } else { //verify that item drops from a single zone
				        $zoneBnetID = false;
				        foreach ($dataArr as $data) {
					        if ($zoneBnetID !== false && $zoneBnetID != $data['location'][0]) {
						        $this->info(' - Item comes from multiple zones: ' . $source->item->bnet_id . ' (ItemID: ' . $source->item->id . ')');
						        /*
						        $source->item_source_type_id = 3;
						        $source->bnet_source_id = null;
						        $source->zone_id = null;
						        */
						        break;
					        } else {
						        $zoneBnetID = $data['location'][0];
					        }
				        }
				        
				        if ($zoneBnetID && $zoneBnetID != $source->zone->bnet_id) {
					        $newZone = Zone::where('bnet_id', '=', $zoneBnetID)->first();
				        } else {
					        $newZone = false;
				        }
				        
				        if ($newZone) {
					        $source->zone_id = $newZone->id;
					        $source->bnet_source_id = $newZone->bnet_id;
					        $this->line('- Updating zone id');
				        }
			        }			        
	        
			        $source->dynamic_quest_rewards = 1;
			        $source->save();
		        }
	        }
        }
    }
    
    private function getWowheadSourceData($itemID) {
	    if (self::$client === null) {
			self::$client = new \GuzzleHttp\Client();
		}
		
	    $url = 'http://www.wowhead.com/item=' . $itemID;
	    
	    try {
			$res = self::$client->request('GET', $url);
		} catch (\Exception $e) {
			return false;
		}
		
		if ($res) {
			$html = (string)$res->getBody();
		} else {
			return false;
		}
		
		$matches = [];
		$json = false;
		$arr = explode('new Listview', $html);
	
		foreach ($arr as $str) {
			$str = preg_replace('/[\n\r]/', '', $str);
			preg_match_all('/\(\{template\: \'npc\'(.+), data\: (?P<data>\[(.+)\])\}\);/', $str, $matches);
			
			if (@$matches['data'][0]) {
				$json = $matches['data'][0];
				break;
			}
		}
		
		if ($json) {
			$json = preg_replace('/(")?(count)(?(1)\1|)/', '"count"', $json);
			$json = preg_replace('/(")?(outof)(?(1)\1|)/', '"outof"', $json);
			$json = preg_replace('/(")?(personal_loot)(?(1)\1|)/', '"personal_loot"', $json);
			
			return json_decode($json, true);
		} else {
			return false;
		}
    }
}
