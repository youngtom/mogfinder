<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use App\Item;
use App\ItemDisplay;
use App\Character;
use App\Faction;
use App\Realm;
use App\Race;
use App\Libraries\LuaParser;
use App\BnetWowApi;
use App\UserItem;
use App\ItemLocation;
use App\ItemSource;
use App\ItemSourceType;
use App\Mogslot;
use App\FileUpload;
use App\Zone;
use App\Difficulty;
use App\Boss;
use DB;

class TestController extends Controller
{
	public function index() {
		Realm::importRealms('cn');
		Realm::importRealms('tw');
		Realm::importRealms('tw');
		Realm::importRealms('kr');
		die;
		$legacyItemIDs = ItemSource::where('item_source_type_id', '=', 17)->get(['item_id'])->lists('item_id')->toArray();
		$sourcedItemIDs = ItemSource::where('item_source_type_id', '<>', 17)->whereIn('item_id', $legacyItemIDs)->get(['item_id'])->lists('item_id')->toArray();
		
		$legacyItemIDs = array_diff($legacyItemIDs, $sourcedItemIDs);
		
		$items = Item::where('transmoggable', '=', 1)->whereIn('id', $legacyItemIDs)->where('item_bind', '=', 1)->whereNotIn('quality', [7, 5])->orderBy('bnet_id', 'ASC')->get();
		
		foreach ($items as $item) {
			$out[] = 'Item: <a href="http://www.wowhead.com/item=' . $item->bnet_id . '" class="item-link q' . $item->quality . '" rel="' . $item->getWowheadMarkup() . '">[' . $item->name . ']</a>';
		}
		
		return view('test')->with('out', $out)->with('newline', "<br>");
		
		die;
		$sources = ItemSource::whereNotNull('item_currency_info')->get()->groupBy('item_id');
		
		foreach ($sources as $itemID => $sourceArr) {
			$item = Item::find($itemID);			
			
			$out[] = 'Item: <a href="http://www.wowhead.com/item=' . $item->bnet_id . '" class="q' . $item->quality . '" rel="' . $item->getWowheadMarkup() . '">[' . $item->name . ']</a>';
			
			foreach ($sourceArr as $source) {
				$itemArr = json_decode($source->item_currency_info, true);
				
				foreach ($itemArr as $_arr) {
					list($bnetID, $count) = $_arr;
					
					$sItem = Item::where('bnet_id', '=', $bnetID)->first();
					
					if ($sItem) {
						$token = ($sItem->isItemToken()) ? 1 : 0;
						$out[] = $token . ': <a href="http://www.wowhead.com/item=' . $sItem->bnet_id . '" class="q' . $sItem->quality . '" rel="' . $sItem->getWowheadMarkup() . '">[' . $sItem->name . ']</a> x' . $count;
					} else {
						die('BnetID not found: ' . $bnetID);
					}
				}
			}
		}
		
		return view('test')->with('out', $out)->with('newline', "<br>");
		
		$file = file(storage_path() . '/logs/PROD.laravel6.log');
		
		$_out = [
			'boss' => [],
			'zone' => [],
			'no-location' => [],
			'no-location-object' => [],
			'multiple-locs' => [],
			'no-vendor-loc' => [],
			'multiple-spells' => [],
			'multiple-boss-drops' => [],
			'no-source-data' => []
		];
		
		foreach ($file as $line) {
			
			if (stristr($line, 'production.INFO')) {
				list($timestamp, $typeStr, $itemStr, $bnetID) = explode(': ', trim($line));
				$bnetID = trim(str_replace(')', '', $bnetID));
				if (preg_match_all('/Boss \((\d+)\) not found for item/', $typeStr, $matches)) {
					$bossID = $matches[1][0];
					
					if (!array_key_exists($bossID, $_out['boss'])) {
						$_out['boss'][$bossID] = [];
					}
					
					if (!in_array($bnetID, $_out['boss'][$bossID])) {
						$_out['boss'][$bossID][] = $bnetID;
					}
				} elseif (preg_match_all('/Zone \((\d+)\) not found for item/', $typeStr, $matches)) {
					$zoneID = $matches[1][0];
					
					if (!array_key_exists($zoneID, $_out['zone'])) {
						$_out['zone'][$zoneID] = [];
					}
					
					if (!in_array($bnetID, $_out['zone'][$zoneID])) {
						$_out['zone'][$zoneID][] = $bnetID;
					}
				} elseif ($typeStr == 'Location info not available for item drop') {
					if (!in_array($bnetID, $_out['no-location'])) {
						$_out['no-location'][] = $bnetID;
					}
				} elseif ($typeStr == 'Location info not available for item in object') {
					if (!in_array($bnetID, $_out['no-location-object'])) {
						$_out['no-location-object'][] = $bnetID;
					}
				} elseif ($typeStr == 'Creating multiple boss drops for item') {
					if (!in_array($bnetID, $_out['multiple-boss-drops'])) {
						$_out['multiple-boss-drops'][] = $bnetID;
					}
				} elseif (preg_match_all('/Multiple locations for NPC \((\d+)\) for item/', $typeStr, $matches)) {
					$bossID = $matches[1][0];
					
					if (!array_key_exists($bossID, $_out['multiple-locs'])) {
						$_out['multiple-locs'][$bossID] = [];
					}
					
					if (!in_array($bnetID, $_out['multiple-locs'][$bossID])) {
						$_out['multiple-locs'][$bossID][] = $bnetID;
					}
				} elseif (preg_match_all('/NPC \((\d+)\) location not found for item/', $typeStr, $matches)) {
					$bossID = $matches[1][0];
					
					if (!array_key_exists($bossID, $_out['no-vendor-loc'])) {
						$_out['no-vendor-loc'][$bossID] = [];
					}
					
					if (!in_array($bnetID, $_out['no-vendor-loc'][$bossID])) {
						$_out['no-vendor-loc'][$bossID][] = $bnetID;
					}
				} elseif ($typeStr == 'Item created by multiple spells') {
					if (!in_array($bnetID, $_out['multiple-spells'])) {
						$_out['multiple-spells'][] = $bnetID;
					}
				} elseif ($typeStr == 'Source data not found for item') {
					if (!in_array($bnetID, $_out['no-source-data'])) {
						$_out['no-source-data'][] = $bnetID;
					}
				}
			}
		}
		//dd($_out);
		
		$out = [];
		
		foreach ($_out as $label => $dataArr) {
			if (count($dataArr)) {
				if ($label == 'boss') {
					$out[] = '<h4>NPCs (instance but not boss) - added</h4>';
					foreach ($dataArr as $bossID => $itemIDArr) {
						$str = '<a href="http://www.wowhead.com/npc=' . $bossID . '">NPC ' . $bossID . '</a> - Items: ';
						foreach ($itemIDArr as $itemID) {
							$str .= '<a href="http://www.wowhead.com/item=' . $itemID . '">' . $itemID . '</a> ';
						}
						$str .= '<br>';
						$out[] = $str;
					}
				} elseif ($label == 'zone') {
					$out[] = '<h4>Zones</h4>';
					foreach ($dataArr as $zoneID => $itemIDArr) {
						$str = '<a href="http://www.wowhead.com/zone=' . $zoneID . '">Zone ' . $zoneID . '</a> - Items: ';
						foreach ($itemIDArr as $itemID) {
							$str .= '<a href="http://www.wowhead.com/item=' . $itemID . '">' . $itemID . '</a> ';
						}
						$str .= '<br>';
						$out[] = $str;
					}
				} elseif ($label == 'no-location') {
					$out[] = '<h4>No location (single NPC drop) - not added</h4>';
					foreach ($dataArr as $itemID) {
						$out[] = '<a href="http://www.wowhead.com/item=' . $itemID . '">' . $itemID . '</a><br>';
					}
				} elseif ($label == 'no-location-object') {
					$out[] = '<h4>No location (object) - not added</h4>';
					foreach ($dataArr as $itemID) {
						$out[] = '<a href="http://www.wowhead.com/item=' . $itemID . '">' . $itemID . '</a><br>';
					}
				} elseif ($label == 'multiple-boss-drops') {
					$out[] = '<h4>Multiple boss drops - added</h4>';
					foreach ($dataArr as $itemID) {
						$out[] = '<a href="http://www.wowhead.com/item=' . $itemID . '">' . $itemID . '</a><br>';
					}
				} elseif ($label == 'multiple-locs') {
					$out[] = '<h4>NPCs in multiple locations (add sources manually) - not added</h4>';
					foreach ($dataArr as $bossID => $itemIDArr) {
						$str = '<a href="http://www.wowhead.com/npc=' . $bossID . '">NPC ' . $bossID . '</a> - Items: ';
						foreach ($itemIDArr as $itemID) {
							$str .= '<a href="http://www.wowhead.com/item=' . $itemID . '">' . $itemID . '</a> ';
						}
						$str .= '<br>';
						$out[] = $str;
					}
				} elseif ($label == 'no-vendor-loc') {
					$out[] = '<h4>No location for vendor NPCs (might be legacy) - added</h4>';
					foreach ($dataArr as $bossID => $itemIDArr) {
						$str = '<a href="http://www.wowhead.com/npc=' . $bossID . '">NPC ' . $bossID . '</a> - Items: ';
						foreach ($itemIDArr as $itemID) {
							$str .= '<a href="http://www.wowhead.com/item=' . $itemID . '">' . $itemID . '</a> ';
						}
						$str .= '<br>';
						$out[] = $str;
					}
				} elseif ($label == 'multiple-spells') {
					$out[] = '<h4>Multiple Spells - not added</h4>';
					foreach ($dataArr as $itemID) {
						$out[] = '<a href="http://www.wowhead.com/item=' . $itemID . '">' . $itemID . '</a><br>';
					}
				} elseif ($label == 'no-source-data') {
					$out[] = '<h4>No source data</h4>';
					foreach ($dataArr as $itemID) {
						$item = Item::where('bnet_id', '=', $itemID)->first();
						$out[] = '<a href="http://www.wowhead.com/item=' . $itemID . '">' . $itemID . '</a> - ' . $item->itemSources()->where('item_source_type_id', '<>', 17)->get()->count() . '<br>';
					}
				}
			}
			
			$out[] = '<br>';
		}
	   
	   return view('test')->with('out', $out)->with('newline', "\n");
		
		/*
		$sources = ItemSource::where('item_source_type_id', '=', 6)->orderBy('bnet_source_id', 'ASC')->get()->groupBy('bnet_source_id');
	    $out = [];
	    
	    foreach ($sources as $bnetSourceID => $sourceArr) {
		    $out[] = '<h4>SourceID: ' . $bnetSourceID . '</h4>';
		    foreach ($sourceArr as $source) {
			    if ($source->item->transmoggable) {
				    $otherSources = ItemSource::where('item_id', '=', $source->item_id)->where('item_source_type_id', '<>', 6)->get();
				    
				    $out[] = '<a href="' . $source->getWowheadLink($source->item) . '">' . $source->getSourceText() . '</a>: <a href="http://www.wowhead.com/item=' . $source->item->bnet_id . '" class="q' . $source->item->quality . '" rel="' . $source->item->getWowheadMarkup() . '">[' . $source->item->name . ']</a> (' . $otherSources->count() . ')<br>';
				    
				    if ($otherSources->count()) {
					    $out[] = '<ul>';
					    foreach ($otherSources as $otherSource) {
						    $out[] = '<li><a href="' . $otherSource->getWowheadLink($otherSource->item) . '">' . $otherSource->getSourceText() . '</a></li>';
					    }
					    $out[] = '</ul>';
				    }
				}
			}
	    }
	    
		$bosses = [
			'1048|71543' => '110784,110785,112382,112383,112416,112417,112418,112419,112420,112421,112422,112423,112424,112425,112425,112425,112428,112429,112445,112447,112448',
			'1057|71734' => '112702,112949,112950,112951,112952,112953'
		];
		
		foreach ($bosses as $bossStr => $itemStr) {
			list($bossID, $bossBnetID) = explode('|', $bossStr);
			$itemBnetIDs = explode(',', $itemStr);
			
			$boss = Boss::findOrFail($bossID);
			
			$items = Item::whereIn('bnet_id', $itemBnetIDs)->whereNotIn('id', function ($query) {
		    	$query->select('item_id')->from('item_sources');
		    })->get();
		    
		    foreach ($items as $item) {
			    $source = new ItemSource;
			    $source->item_id = $item->id;
			    $source->item_source_type_id = 4;
			    $source->bnet_source_id = $boss->bnet_id;
			    $source->boss_id = $boss->id;
			    $source->zone_id = $boss->zone_id;
			    $source->import_source = 'custom';
			    $source->save();
		    }
		}
		dd($bosses);
		*/
	}
	
	public function legacyItemInfo() {
		$items = Item::whereIn('id', function ($query) {
		    $query->select('item_id')->from('item_sources')->where('item_source_type_id', '=', 17);
	    })->where('transmoggable', '=', 1)->orderBy('bnet_id', 'ASC')->get();
		
		$out = [];
		$ids = [];
		foreach ($items as $item) {
			if ($item->itemSources->count() > 1) {
				$ids[] = $item->id;
				$out[] = '<a href="http://www.wowhead.com/item=' . $item->bnet_id . '" class="q' . $item->quality . '" rel="' . $item->getWowheadMarkup() . '">[' . $item->name . ']</a>';
				foreach ($item->itemSources as $source) {
					$out[] = '-- <a href="' . $source->getWowheadLink($item) . '">' . $source->getSourceText() . '</a>';
					
					if ($source->item_source_type_id != 17) {
						//$source->delete();
					}
				}
			}
		}
		
		echo implode(',', $ids); die;
		
		return view('test')->with('out', $out)->with('newline', "<br>");
	}
	
	public function worldDropInfo() {
		$items = Item::whereIn('id', function ($query) {
		    $query->select('item_id')->from('item_sources')->where('item_source_type_id', '=', 3);
	    })->where('transmoggable', '=', 1)->orderBy('bnet_id', 'ASC')->get();
		
		$out = [];
		
		foreach ($items as $item) {
			if ($item->itemSources->count() > 1) {
				$out[] = '<a href="http://www.wowhead.com/item=' . $item->bnet_id . '" class="q' . $item->quality . '" rel="' . $item->getWowheadMarkup() . '">[' . $item->name . ']</a>';
				foreach ($item->itemSources as $source) {
					$out[] = '-- <a href="' . $source->getWowheadLink($item) . '">' . $source->getSourceText() . '</a>';
					
					if ($source->item_source_type_id == 3) {
						$source->delete();
					}
				}
			}
		}
		
		return view('test')->with('out', $out)->with('newline', "<br>");
	}
	
	public function objectDropInfo() {
		$items = Item::whereIn('id', function ($query) {
		    $query->select('item_id')->from('item_sources')->where('item_source_type_id', '=', 6);
	    })->where('transmoggable', '=', 1)->orderBy('bnet_id', 'ASC')->get();
		
		$out = [];
		
		foreach ($items as $item) {
			if ($item->itemSources->count() > 1) {
				$out[] = '<a href="http://www.wowhead.com/item=' . $item->bnet_id . '" class="q' . $item->quality . '" rel="' . $item->getWowheadMarkup() . '">[' . $item->name . ']</a>';
				foreach ($item->itemSources as $source) {
					$out[] = '-- <a href="' . $source->getWowheadLink($item) . '">' . $source->getSourceText() . '</a>';
					
					if ($source->item_source_type_id == 6 && $item->itemSources->where('item_source_type_id', 4)->count()) {
						$source->delete();
					}
				}
			}
		}
		
		return view('test')->with('out', $out)->with('newline', "<br>");
	}
	
	public function zoneDropInfo() {
		$items = Item::whereIn('id', function ($query) {
		    $query->select('item_id')->from('item_sources')->where('item_source_type_id', '=', 15);
	    })->where('transmoggable', '=', 1)->orderBy('bnet_id', 'ASC')->get();
		
		$out = [];
		
		foreach ($items as $item) {
			if ($item->itemSources->count() > 1) {
				$out[] = '<a href="http://www.wowhead.com/item=' . $item->bnet_id . '" class="q' . $item->quality . '" rel="' . $item->getWowheadMarkup() . '">[' . $item->name . ']</a> - ' . $item->id;
				foreach ($item->itemSources as $source) {
					$out[] = '-- <a href="' . $source->getWowheadLink($item) . '">' . $source->getSourceText() . '</a>';
					
					if ($source->item_source_type_id != 15) {
						//$source->delete();
					}
				}
			}
		}
		
		return view('test')->with('out', $out)->with('newline', "<br>");
	}
	
    public function checkDeletedSources($id) {
	    $sourceData = file(storage_path() . '/app/imports/sourcedata.out_' . $id . '.txt');
	    $out = [];
	    
	    foreach ($sourceData as $str) {
		    preg_match_all('/Deleting source - itemID: (\d+) bnetID: (\d+) typeID: (\d+)/', trim($str), $matches);
		    
		    $itemID = $matches[1][0];
		    $sourceBnetID = $matches[2][0];
		    $sourceTypeID = $matches[3][0];
		    
		    $item = Item::find($itemID);
		    $sourceType = ItemSourceType::find($sourceTypeID);
		    
		    if ($item) {
			    $replace = [
					'{$bnet_id}' => $sourceBnetID
				];
				
				$sourceLink = 'http://www.wowhead.com/' . strtr($sourceType->wowhead_link_format, $replace);
			    
			    $out[] = $sourceTypeID . ': <a href="' . $sourceLink . '">' . $sourceType->simple_label . '</a>: <a href="http://www.wowhead.com/item=' . $item->bnet_id . '" class="q' . $item->quality . '" rel="' . $item->getWowheadMarkup() . '">[' . $item->name . ']</a> -- ' . $str;
		    }
	    }
	    return view('test')->with('out', $out)->with('newline', "<br>");
    }
    
    public function listSourcelessItems() {
	    $items = Item::whereNotIn('id', function ($query) {
		    $query->select('item_id')->from('item_sources');
	    })->where('transmoggable', '=', 1)->orderBy('bnet_id', 'ASC')->get();
	    $out = [];
	    
		foreach ($items as $item) {
			$out[] = $item->bnet_id . ': <a href="http://www.wowhead.com/item=' . $item->bnet_id . '" class="q' . $item->quality . '" rel="' . $item->getWowheadMarkup() . '">[' . $item->name . ']</a> (' . $item->id . ')';
			/*
			$otherItemIDs = Item::where('bnet_id', '=', $item->bnet_id)->orWhere('name', '=', $item->name)->get()->lists('id')->toArray();
			
			$sources = ItemSource::whereIn('item_id', $otherItemIDs)->orderBy('item_id', 'ASC')->get();
			
			if ($sources->count()) {
				$out[] = '<ul>';
				foreach ($sources as $source) {
					$out[] = '<li><a href="' . $source->getWowheadLink($source->item) . '">' . $source->getSourceText() . '</a>: <a href="http://www.wowhead.com/item=' . $source->item->bnet_id . '" class="q' . $source->item->quality . '" rel="' . $source->item->getWowheadMarkup() . '">[' . $source->item->name . ']</a></li>';
				}
				$out[] = '</ul>';	
			}
			
			$out[] = '<br><br>';
			*/
		}
		
		return view('test')->with('out', $out)->with('newline', "<br>")->with('pagination', false);
    }
    
    public function listSources($id) {
	    $sources = ItemSource::where('created_at', '>=', '2016-05-16')->orderBy('bnet_source_id', 'ASC')->get()->groupBy('item_source_type_id');
	    $out = [];
	    
	    foreach ($sources as $sourceTypeID => $sourceArr) {
		    $out[] = 'BnetSourceID: ' . $sourceTypeID;
		    foreach ($sourceArr as $source) {
			    if ($source->item) {
				    $out[] = '<a href="http://www.wowhead.com/' . $source->getWowheadMarkup($source->item) . '">' . $source->getSourceText() . '</a>: <a href="http://www.wowhead.com/item=' . $source->item->bnet_id . '" class="q' . $source->item->quality . '" rel="' . $source->item->getWowheadMarkup() . '">[' . $source->item->name . ']</a> - ' . $source->item->id;
				}
			}
	    }
	    
	    return view('test')->with('out', $out)->with('newline', "<br>");
    }
}
