<?php

namespace App\Console\Commands\wowhead;

use Illuminate\Console\Command;
use App\Item;
use App\ItemSource;

class ImportQuestItemData extends Command
{
	private static $client = null;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wowhead:items:import-quest-data {filename}';

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
        $filename = $this->argument('filename');
        if (!$filename || !file_exists($filename)) {
	        $this->error('Please specify a valid file');
	        return;
        }
        
        $json = file_get_contents($filename);
        
        $itemsArr = json_decode($json, true);
        
        $bar = $this->output->createProgressBar(count($itemsArr));
        
        foreach ($itemsArr as $itemArr) {
	        $bnetID = trim($itemArr['id']);
	        
	        $items = Item::where('bnet_id', '=', $bnetID)->where('transmoggable', '=', 1)->where('imported_from_bnet', '=', 0)->get();
	        
	        if ($items->count()) {
		        $scrapeWowhead = true;
		        if (@$itemArr['source'] && $sourceMoreIndex = array_search(4, $itemArr['source'])) {
			        $sourceArr = (@$itemArr['sourcemore'][$sourceMoreIndex]) ?: false;
			        
			        if ($sourceArr && array_key_exists('ti', $sourceArr)) {
				        foreach ($items as $item) {
						    $this->addWowheadItemSource($item, $sourceArr['ti']);
				        }
				        $scrapeWowhead = false;
			        }
		        }
		        
		        if ($scrapeWowhead) {
			        $questsArr = $this->getWowheadQuestData($bnetID);
			        
			        if ($questsArr) {
				        $this->line('Importing wowhead data for: ' . $bnetID);
				        
						foreach ($questsArr as $questArr) {
							$questID = $questArr['id'];
							$allQuestItems = [$bnetID];
							
							if (@$questArr['itemchoices']) {
								foreach ($questArr['itemchoices'] as $_itemArr) {
									$allQuestItems[] = $_itemArr[0];
								}
							}
							
							if (@$questArr['itemrewards']) {
								foreach ($questArr['itemrewards'] as $_itemArr) {
									$allQuestItems[] = $_itemArr[0];
								}
							}
							
							$allQuestItemIDs = array_unique($allQuestItems);
							$this->addWowheadItemSourceByBnetIDs($allQuestItemIDs, $questID);
						}
					} else {
						$this->error('Unable to pull wowhead data for item: ' . $bnetID);
					}
		        }
	        }
			
	        $bar->advance();
        }
        
        $bar->finish();
    }
    
    private function addWowheadItemSourceByBnetIDs($bnetIDArr, $sourceID) {
	    $items = Item::whereIn('bnet_id', $bnetIDArr)->where('transmoggable', '=', 1)->where('imported_from_bnet', '=', 0)->get();
	    
	    foreach ($items as $item) {
		    $this->addWowheadItemSource($item, $sourceID);
		    $item->imported_from_bnet = 1;
		    $item->save();
	    }
    }
    
    private function addWowheadItemSource(Item $item, $sourceID) {
	    $itemSource = ItemSource::where('item_source_type_id', '=', 7)->where('bnet_source_id', '=', $sourceID)->where('item_id', '=', $item->id)->first();
	    
	    if (!$itemSource) {
		    $itemSource = new ItemSource;
		    $itemSource->item_id = $item->id;
		    $itemSource->item_source_type_id = 7;
		    $itemSource->bnet_source_id = $sourceID;
		    $itemSource->import_source = 'wowhead';
		    $itemSource->save();
	    }
    }
    
    private function getWowheadQuestData($itemID) {
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
		preg_match_all('/new Listview\(\{template\: \'quest\'(.+), data\: (?P<data>\[.+\])(.+)\)\;/', $html, $matches);
		
		$json = $matches['data'][0];
		return json_decode($json, true);
    }
}
