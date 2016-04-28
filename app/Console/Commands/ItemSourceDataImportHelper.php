<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;
use App\ItemSource;
use App\Boss;
use App\Zone;
use App\Difficulty;


class ItemSourceDataImportHelper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:import-sources {sourceID}';

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
		$sourceToImport = $this->argument('sourceID');
		
		if (!$sourceToImport) {
			$this->error('Please specify a source ID');
		}
		
        list($modes, $encounters, $instances) = file(storage_path() . '/app/imports/extradata.txt');
		$modes = explode('|', $modes);
		$encounters = explode('|', $encounters);
		$instances = explode('|', $instances);
		
		$bossArr = $zoneArr = $difficultyArr = [];
		
		foreach ($encounters as $idx => $encounter) {
			$bosses = Boss::where('name', '=', trim($encounter))->get();
			$bossArr[$idx + 1] = ($bosses->count()) ? $bosses : false;
		}
		
		foreach ($instances as $idx => $instance) {
			$zone = Zone::where('name', '=', trim($instance))->first();
			$zoneArr[$idx + 1] = ($zone) ?: false;
		}
		
		foreach ($modes as $idx => $mode) {
			$difficulties = Difficulty::where('shortname', '=', trim($mode))->orWhere('name', '=', trim($mode))->get();
			$difficultyArr[$idx + 1] = ($difficulties->count()) ? $difficulties : false;
			
			if (!$difficulties->count()) {
				$this->error($mode);
			}
		}
		
		$sourceData = file(storage_path() . '/app/imports/sourcedata_new.txt');
		$sourceToImportStr = ($sourceToImport) ?: 'all';
		$fp = fopen(storage_path() . '/app/imports/sourcedata.out_' . $sourceToImportStr . '.txt', 'w');
		
		$lineByItem = [];
		
		$diffToBonusMap = [
			2 => [642]
		];
		
		$lineCount = 0;
		
		foreach ($sourceData as $str) {
			preg_match_all('/\[(")?(?P<itemid>[0-9:]+)(")?\] \= \{(?P<sourceid>\d)([#,](?P<data>.+))?\},/', $str, $matches);
			
			if (count($matches)) {
				$itemID = @$matches['itemid'][0];
				$sourceID = @$matches['sourceid'][0];
				$data = @$matches['data'][0];
				
				if ($sourceToImport == false || $sourceToImport == $sourceID) {
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
						if ($sourceID == 8 || $sourceID == 4) {
							list($_null, $existingData) = (@$lineByItem[$bnetID]['default']) ? explode('||', $lineByItem[$bnetID]['default']) : [null, ''];
							$data = $existingData ? implode(',', array_unique(array_merge(explode(',', $data), explode(',', $existingData)))) : $data;
							$bonus = 'default';
						}
						
						$lineByItem[$bnetID][$bonus] = $sourceID . '||' . $data;
						$lineCount++;
					} else {
						$this->line('Problem with line: ' . $str);
					}
				}
			}
		}

		$bar = $this->output->createProgressBar($lineCount);
		
		foreach ($lineByItem as $itemID => $lines) {
			$numRows = count($lines);
			
			foreach ($lines as $bonus => $sData) {
				$bonus = ($numRows > 1 && $bonus != 'default') ? $bonus : null;
				
				list($sourceID, $data) = explode('||', $sData);
				
				$sourceArr = explode(',', $data);
				
				if ($sourceID == 8 || $sourceID == 4) {
					$items = Item::where('bnet_id', '=', $itemID)->get();
				} else {
					if ($sourceID == 1 && !$bonus && $numRows > 1) {
						$bonusSearch = array_diff(array_keys($lineByItem[$itemID]), ['default']);
						$items = Item::where('bnet_id', '=', $itemID)->whereNotIn('bonus', $bonusSearch)->get();
					} else {
						$items = Item::where('bnet_id', '=', $itemID)->where('bonus', '=', $bonus)->get();
					}
				}
				
				if (!$items->count()) {
					if ($sourceID == 1) {
						$dropInfo = str_replace('"', '', $sourceArr[0]);
						$dropInfo = str_replace('"', '', $dropInfo);
						$dropInfoArr = explode('#', $dropInfo);
						
						$diff = (@$dropInfoArr[3]) ?: false;
						
						if ($diff && array_key_exists($diff, $diffToBonusMap)) {
							$items = Item::where('bnet_id', '=', $itemID)->whereIn('bonus', $diffToBonusMap[$diff])->get();
						}
					}
				}
				
				if ($items->count()) {					
					if ($sourceID == 1) { // boss drop
						$sourceArr = explode(',', $data);
						$sourceBosses = $sourceBossBnetIDs = [];
						
						foreach ($sourceArr as $sourceInfo) {
							$sourceInfo = str_replace('"', '', $sourceInfo);
							$sourceInfo = str_replace("'", '', $sourceInfo);
							list($raidDungeon, $instanceIdx, $bossIdx, $diffStr) = explode('#', $sourceInfo);
							$diffStr = trim($diffStr);
							$diffArr = ($diffStr) ? explode(':', $diffStr) : false;
							
							if (@$bossArr[$bossIdx] && @$zoneArr[$instanceIdx]) {
								$zone = $zoneArr[$instanceIdx];
								
								$bosses = $bossArr[$bossIdx];
								
								if ($bosses->count() == 1) {
									$boss = $bosses->first();
								} else {
									$boss = $bosses->where('zone_id', $zone->id)->first();
								}
								
								$boss = ($boss) ? $boss->encounter() : false;
								
								$bossDiffs = [];
								if ($diffArr && $boss->zone->available_modes && ($numRows == 1 || $bonus)) {
									foreach ($diffArr as $diffIdx) {
										$difficulties = $difficultyArr[$diffIdx];
										$modeArr = explode(',', $boss->zone->available_modes);
										
										$difficulty = $difficulties->whereIn('label', $modeArr)->first();
										
										if ($difficulty) {
											$bossDiffs[] = $difficulty->id;
										}
									}
								}
								
								if ($boss) {
									$sourceBosses[] = [$boss, $bossDiffs];
									$sourceBossBnetIDs[] = $boss->bnet_id;
								}
							}
						}
						
						if (count($sourceBosses)) {
							foreach ($items as $item) {
								foreach ($item->itemSources as $source) {
									if ($source->item_source_type_id != 2 && ($source->item_source_type_id != 4 || !in_array($source->bnet_source_id, $sourceBossBnetIDs))) {
										fwrite($fp, 'Deleting source - itemID: ' . $item->id . ' bnetID: ' . $source->bnet_source_id . ' typeID: ' . $source->item_source_type_id . "\n");
										$source->delete();
									}
								}
								
								foreach ($sourceBosses as list($boss, $bossDiffs)) {
									$source = ItemSource::where('item_id', '=', $item->id)->where('bnet_source_id', '=', $boss->bnet_id)->where('item_source_type_id', '=', 4)->first();
									
									if (!$source) {
										$source = new ItemSource;
										$source->item_id = $item->id;
										$source->item_source_type_id = 4;
										$source->bnet_source_id = $boss->bnet_id;
										$source->import_source = 'ItemSourceDataImportHelper';
									}
									
									$source->boss_id = $boss->id;
									$source->zone_id = $boss->zone_id;
									$source->difficulties = (count($bossDiffs)) ? implode(',', $bossDiffs) : null;
									$source->save();
								}
							}
						}
					} elseif ($sourceID == 2) { //Quest
						
					} elseif ($sourceID == 3) { //Vendor
						
					} elseif ($sourceID == 4) { //World Drop
						$sourceArr = explode(',', trim($data));
						
						$sourceBnetID = $sourceArr[0];
						
						if ($sourceBnetID) {
							$zone = Zone::where('bnet_id', '=', $sourceBnetID)->first();
						}
						
						if (count($sourceArr) == 1 && $sourceBnetID && $zone) {
							foreach ($items as $item) {
								foreach ($item->itemSources as $source) {
									if ($source->item_source_type_id == 3) {
										$source->item_source_type_id = 15;
										$source->bnet_source_id = $zone->bnet_id;
										$source->zone_id = $zone->id;
										$source->import_source = 'ItemSourceDataImportHelper';
										$source->save();
									} elseif ($source->item_source_type_id == 6) {
										$source->zone_id = $zone->id;
										$source->save();
									}
								}
								
								if (!$item->itemSources->count()) {
									$source = ItemSource::where('item_id', '=', $item->id)->where('bnet_source_id', '=', $sourceBnetID)->where('item_source_type_id', '=', 15)->first();
									
									if (!$source) {
										$source = new ItemSource;
										$source->item_id = $item->id;
										$source->bnet_source_id = $sourceBnetID;
										$source->item_source_type_id = 15;
										$source->zone_id = $zone->id;
										$source->import_source = 'ItemSourceDataImportHelper';
										$source->save();
									}
								}
							}
						} else {
							foreach ($items as $item) {
								foreach ($item->itemSources as $source) {
									if ($source->item_source_type_id != 3 && $source->item_source_type_id != 4 && $source->item_source_type_id != 15 && $source->item_source_type_id != 6) {
										fwrite($fp, 'Deleting source - itemID: ' . $item->id . ' bnetID: ' . $source->bnet_source_id . ' typeID: ' . $source->item_source_type_id . " (World)\n");
										$source->delete();
									}
								}
								
								if (!$item->itemSources->count()) {
									$source = ItemSource::where('item_id', '=', $item->id)->where('item_source_type_id', '=', 3)->first();
									
									if (!$source) {
										$source = new ItemSource;
										$source->item_id = $item->id;
										$source->item_source_type_id = 3;
										$source->import_source = 'ItemSourceDataImportHelper';
										$source->save();
									}
								}
							}
						}
					} elseif ($sourceID == 5) { //Legacy
						foreach ($items as $item) {
							foreach ($item->itemSources as $source) {
								if ($source->item_source_type_id != 5) {
									fwrite($fp, 'Deleting source - itemID: ' . $item->id . ' bnetID: ' . $source->bnet_source_id . ' typeID: ' . $source->item_source_type_id . "\n");
									$source->delete();
								}
							}
															
							$source = ItemSource::where('item_id', '=', $item->id)->where('item_source_type_id', '=', 17)->first();
							
							if (!$source) {
								$source = new ItemSource;
								$source->item_id = $item->id;
								$source->item_source_type_id = 17;
								$source->save();
							}
						}
					} elseif ($sourceID == 6) { //Created
						$sourceArr = explode(',', $data);
						$sourceBnetIDs = array_map('abs', $sourceArr);
						
						foreach ($items as $item) {
							foreach ($item->itemSources as $source) {
								if (($source->item_source_type_id != 12 && $source->item_source_type_id != 16) || !in_array($source->bnet_source_id, $sourceBnetIDs)) {
									fwrite($fp, 'Deleting source - itemID: ' . $item->id . ' bnetID: ' . $source->bnet_source_id . ' typeID: ' . $source->item_source_type_id . "\n");
									$source->delete();
								}
							}
							
							foreach ($sourceArr as $sourceBnetID) {
								$newSourceID = ($sourceBnetID > 0) ? 16 : 12; // 16 - created from, 12 - contained in
								$source = ItemSource::where('item_id', '=', $item->id)->where('bnet_source_id', '=', abs($sourceBnetID))->whereIn('item_source_type_id', [12, 16])->first();
								
								if (!$source) {
									$source = new ItemSource;
									$source->item_id = $item->id;
									$source->bnet_source_id = abs($sourceBnetID);
									$source->import_source = 'ItemSourceDataImportHelper';
								}
								$source->item_source_type_id = $newSourceID;
								$source->save();
							}
						}
					} elseif ($sourceID == 7) { //Achievement
						$sourceArr = explode(',', $data);
						
						foreach ($items as $item) {
							foreach ($item->itemSources as $source) {
								if ($source->item_source_type_id != 13 || !in_array($source->bnet_source_id, $sourceArr)) {
									fwrite($fp, 'Deleting source - itemID: ' . $item->id . ' bnetID: ' . $source->bnet_source_id . ' typeID: ' . $source->item_source_type_id . "\n");
									//$source->delete();
								}
							}
							
							foreach ($sourceArr as $sourceBnetID) {
								break;
								$source = ItemSource::where('item_id', '=', $item->id)->where('bnet_source_id', '=', $sourceBnetID)->where('item_source_type_id', '=', 13)->first();
								
								if (!$source) {
									$source = new ItemSource;
									$source->item_id = $item->id;
									$source->bnet_source_id = $sourceBnetID;
									$source->item_source_type_id = 13;
									$source->import_source = 'ItemSourceDataImportHelper';
									$source->save();
								}
							}
						}
					} elseif ($sourceID == 8) { //Profession
						
					}
				}
				$bar->advance();
			}
		}
		
		$bar->finish();
		fclose($fp);
    }
}
