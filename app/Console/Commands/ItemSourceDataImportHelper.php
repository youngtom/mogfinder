<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;

class ItemSourceDataImportHelper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:import-sources';

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
        list($modes, $encounters, $instances) = file(storage_path() . '/app/imports/extradata.txt');
		$modes = explode('|', $modes);
		$encounters = explode('|', $encounters);
		$instances = explode('|', $instances);
		
		$sourceData = file(storage_path() . '/app/imports/sourcedata_new.txt');
		$fp = fopen(storage_path() . '/app/imports/sourcedata.out_new.txt', 'a');
		
		$bar = $this->output->createProgressBar(count($sourceData));
		
		$lineByItem = [];
		
		$diffToBonusMap = [
			2 => [642]
		];
		
		foreach ($sourceData as $str) {
			preg_match_all('/\[(")?(?P<itemid>[0-9:]+)(")?\] \= \{(?P<sourceid>\d)([#,](?P<data>.+))?\},/', $str, $matches);
			
			if (count($matches)) {
				$itemID = @$matches['itemid'][0];
				$sourceID = @$matches['sourceid'][0];
				$data = @$matches['data'][0];
				
				if (strpos($itemID, ':')) {
					list($bnetID, $bonus) = explode(':', $itemID);
				} else {
					$bnetID = $itemID;
					$bonus = null;
				}
				
				if ($itemID && $sourceID) {
					if (!array_key_exists($bnetID, $lineByItem)) {
						$lineByItem[$bnetID] = [];
					}
					
					$bonus = ($bonus) ?: 'default';
					$lineByItem[$bnetID][$bonus] = $sourceID . '||' . $data;
				} else {
					$this->line('Problem with line: ' . $str);
				}
			}
		}
		
		foreach ($lineByItem as $itemID => $lines) {
			$numRows = count($lines);
			
			foreach ($lines as $bonus => $sData) {
				$bonus = ($numRows > 1 && $bonus != 'default') ? $bonus : null;
				
				list($sourceID, $data) = explode('||', $sData);
				$sourceArr = explode(',', $data);
				
				if ($sourceID == 8) {
					$items = Item::where('bnet_id', '=', $itemID)->get();
				} else {
					$items = Item::where('bnet_id', '=', $itemID)->where('bonus', '=', $bonus)->get();
				}
				
				if (!$items->count()) {
					if ($sourceID == 1) {
						$dropInfo = str_replace('"', '', $sourceArr[0]);
						$dropInfo = str_replace('"', '', $dropInfo);
						$dropInfoArr = explode('#', $dropInfo);
						
						$diff = (@$dropInfoArr[3]) ?: false;
						
						if ($diff && array_key_exists($diff, $diffToBonusMap)) {
							$this->line('Checking alt bonus');
							$items = Item::where('bnet_id', '=', $itemID)->whereIn('bonus', $diffToBonusMap[$diff])->get();
						}
					}
				}
				
				if (!$items->count() && $sourceID != 5) {
					$itemInfo = ($bonus) ? $itemID . ':' . $bonus : $itemID;
					$this->line('No item found: ' . $itemInfo . ' -- ' . $sData);
					fwrite($fp, 'No item found: ' . $itemInfo . ' -- ' . $sData . "\n");
				} else {
					$item = $items->first();
					
					if ($item->quality >= 2 && !$item->transmoggable) {
						$this->line('Item not xmoggable: ' . $itemID);
						fwrite($fp, 'Item not xmoggable: ' . $itemID . "\n");
					}
				}
				$bar->advance();
			}
		}
		
		$bar->finish();
		fclose($fp);
    }
}
