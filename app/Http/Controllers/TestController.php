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
		$item = Item::findItemFromLink('|cff1eff00|Hitem:55774:0:0:0:0:0:-36:132907049:1:0:0:0:0|h[Bluefen Cord of the Sorcerer]|h|r');
		$character = Character::find(182);
		$bound = 0;
		
		if ($character->canUseItem($item)) {
			echo 'Yes';
		}
		
		$alts = $character->user->getOtherCharacters($character, ($bound === 0));
							        
        $found = false;
        $alts->each(function ($alt) use ($item, &$found) { 
	        if ($alt->canUseItem($item)) {
				$found = $alt;
				echo $alt->name;
				return false;
			}
        });
		
		dd($found);
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
	    $itemIDs = array_unique(ItemSource::all()->lists('item_id')->toArray());
	    
	    $items = Item::whereNotIn('id', $itemIDs)->where('transmoggable', '=', 1)->orderBy('bnet_id', 'ASC')->get();
	    $out = [];
	    
		foreach ($items as $item) {
			if (!$item->itemSources->count()) {
				$out[] = $item->bnet_id . ': <a href="http://www.wowhead.com/item=' . $item->bnet_id . '" class="q' . $item->quality . '" rel="' . $item->getWowheadMarkup() . '">[' . $item->name . ']</a>';
				
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
			}
		}
		
		return view('test')->with('out', $out)->with('newline', "\n");
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
