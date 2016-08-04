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
		$items = Item::where('equippable', '=', 1)->get();
		
		foreach ($items as $item) {
			$bonus = ($item->bonus) ? '1:' . $item->bonus : '';
			echo '[' . $item->id . '] = {"|cffa335ee|Hitem:' . $item->bnet_id . ':0:0:0:0:0:0:0:0:0:0:0:' . $bonus . '|h[' . addslashes($item->name) . ']|h|r"},' . "\n";
		}
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
    
    public function unavailableDisplays($format = false) {
	    $displays = ItemDisplay::where('legacy', '=', 1)->orderBy('bnet_display_id', 'ASC')->get();
	    
	    $simple = ['displayInfoId,itemID'];
	    foreach ($displays as $display) {
		    $out[] = '<strong>DisplayInfoId: ' . $display->bnet_display_id . '</strong>';
		    
		    foreach ($display->items as $item) {
			    $out[] = 'Item ' . $item->bnet_id . ': <a href="http://www.wowhead.com/item=' . $item->bnet_id . '" class="item-link q' . $item->quality . '" rel="' . $item->getWowheadMarkup() . '">[' . $item->name . ']</a>';
			    $simple[] = $display->bnet_display_id . ',' . $item->bnet_id;
		    }
		    
		    $out[] = '';
	    }
	    
	    if ($format == 'csv') {
		    @mkdir(storage_path() . '/tmp/');
		    $file = storage_path() . '/tmp/legacy.csv';
		    $fp = fopen($file, 'w');
		    fwrite($fp, implode("\r\n", $simple));
		    fclose($fp);
		    
		    return response()->download($file, 'legacy.csv');
	    }
	    
	    return view('test')->with('out', $out)->with('newline', "<br>");
    }
}
