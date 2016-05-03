<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use App\Item;
use App\Character;
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

class TestController extends Controller
{
	public function index() {
		$itemBnetIDs = [920,1218,1300,1458,1459,1460,1469,1489,1523,1602,1664,1680,1986,1992,2015,2018,2035,2058,2226,2235,3223,3227,3336,3571,3902,4303,4445,4446,5245,5752,5756,5819,6315,7728,7729,7730,7736,7752,7755,7757,7758,7759,7760,8225,9375,9422,9423,9424,9425,9427,9429,9431,9465,9491,9510,10581,17054,17055,17061,18736,18742,18743,18744,18745];
		
		$items = Item::whereIn('bnet_id', $itemBnetIDs)->whereNotIn('id', function ($query) {
	    	$query->select('item_id')->from('item_sources');
	    })->get();
	    
	    foreach ($items as $item) {
		    if ($item->item_bind != 2) {
			    die('invalid item bind for ' . $item->bnet_id);
		    }
		    
		    $source = new ItemSource;
		    $source->item_id = $item->id;
		    $source->item_source_type_id = 17;
		    $source->import_source = 'custom';
		    $source->save();
	    }
		/*
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
			$out[] = $item->bnet_id . ': <a href="http://www.wowhead.com/item=' . $item->bnet_id . '" class="q' . $item->quality . '" rel="' . $item->getWowheadMarkup() . '">[' . $item->name . ']</a>';
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
	    $sources = ItemSource::where('item_source_type_id', '=', $id)->orderBy('bnet_source_id', 'ASC')->get()->groupBy('bnet_source_id');
	    $out = [];
	    
	    foreach ($sources as $bnetSourceID => $sourceArr) {
		    $out[] = 'SourceID: ' . $bnetSourceID;
		    foreach ($sourceArr as $source) {
			    if ($source->item->transmoggable) {
				    $out[] = '<a href="' . $source->getWowheadLink($source->item) . '">' . $source->getSourceText() . '</a>: <a href="http://www.wowhead.com/item=' . $source->item->bnet_id . '" class="q' . $source->item->quality . '" rel="' . $source->item->getWowheadMarkup() . '">[' . $source->item->name . ']</a>';
				}
			}
	    }
	    
	    return view('test')->with('out', $out)->with('newline', "<br>");
    }
}
