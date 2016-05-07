<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Auth;
use App\Character;
use App\ItemDisplay;
use App\UserItemDisplay;
use App\Mogslot;
use App\MogslotCategory;
use App\CharClass;
use App\Faction;
use App\Item;
use App\ItemSource;
use App\ItemSourceType;
use App\Auction;
use DB;
use App\Zone;
use App\ZoneCategory;
use App\Boss;

class ItemsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index($characterURL = false) {
	    $user = Auth::user();
	    
	    if ($characterURL) {
		    $character = Character::where('user_id', '=', $user->id)->where('url_token', '=', $characterURL)->first();
		    
		    if (!$character) {
			    return \App::abort(404);
		    }
		    
		    $classmask = pow(2, $character->class_id);
		    $racemask = pow(2, $character->race_id);
		    $mogslots = Mogslot::whereRaw('allowed_class_bitmask IS NULL OR allowed_class_bitmask & ? <> 0', [$classmask])->orderBy('inventory_type_id', 'ASC')->get();
	    } else {
		    $character = false;
		    $mogslots = Mogslot::orderBy('inventory_type_id', 'ASC')->get();
	    }
	    
	    $mogslotCategories = MogslotCategory::whereIn('id', $mogslots->lists('mogslot_category_id')->toArray())->get()->groupBy('group');
	    $mogslotsByCategory = $mogslots->groupBy('mogslot_category_id');
	    $userMogslotCounts = $totalMogslotCounts = [];
	    
	    foreach ($mogslots as $mogslot) {
		    if ($character) { 
			    $mogslotItemDisplayIDs = array_unique($mogslot->itemDisplays->filter(function ($display) use ($classmask, $racemask) {
				    return (($display->restricted_races === null || ($display->restricted_races & $racemask) != 0) && ($display->restricted_classes === null || ($display->restricted_classes & $classmask) != 0));
				})->lists('id')->toArray());
		    } else {
			    $mogslotItemDisplayIDs = array_unique($mogslot->itemDisplays->lists('id')->toArray());
		    }
		    
		    $totalMogslotCounts[$mogslot->id] = count($mogslotItemDisplayIDs);
		    
		    $mogslotUserDisplays = $user->userItemDisplays()->whereIn('item_display_id', $mogslotItemDisplayIDs)->get();
		    if ($character) {
			    $userMogslotCounts[$mogslot->id] = $mogslotUserDisplays->filter(function ($display) use ($classmask, $racemask) {
				    return (($display->restricted_races === null || ($display->restricted_races & $racemask) != 0) && ($display->restricted_classes === null || ($display->restricted_classes & $classmask) != 0));
			    })->count();
		    } else {
			    $userMogslotCounts[$mogslot->id] = $mogslotUserDisplays->count();
		    }
	    }
	    
	    if ($character) {
		    $characters = Character::where('id', '<>', $character->id)->where('user_id', '=', $user->id)->orderBy('realm_id', 'ASC')->orderBy('name', 'ASC')->get()->groupBy('realm_id');
	    } else {
		    $characters = Character::where('user_id', '=', $user->id)->orderBy('realm_id', 'ASC')->orderBy('name', 'ASC')->get()->groupBy('realm_id');
	    }
	    
	    return view('items.overview')->with('categories', $mogslotCategories)->with('mogslotsByCategory', $mogslotsByCategory)->with('userMogslotCounts', $userMogslotCounts)->with('totalMogslotCounts', $totalMogslotCounts)->with('selectedCharacter', $character)->with('characters', $characters);
    }
    
    public function zoneOverview($characterURL = false) {
	    $user = Auth::user();
	    
	    if ($characterURL) {
		    $character = Character::where('user_id', '=', $user->id)->where('url_token', '=', $characterURL)->first();
		    
		    if (!$character) {
			    return \App::abort(404);
		    }
		    
		    $classmask = pow(2, $character->class_id);
		    $racemask = pow(2, $character->race_id);
		    $mogslotIDs = Mogslot::whereRaw('allowed_class_bitmask IS NULL OR allowed_class_bitmask & ? <> 0', [$classmask])->get()->lists('id')->toArray();
	    } else {
		    $character = false;
		    $mogslotIDs = Mogslot::all()->lists('id')->toArray();
	    }
	    
	    $zones = Zone::orderBy('name', 'ASC')->get();
	    
	    $zones = $zones->filter(function ($zone) use ($mogslotIDs) {
		    return $zone->itemDisplays->whereIn('mogslot_id', $mogslotIDs)->count() >= 1;
	    });
	    
	    $zoneCategories = ZoneCategory::whereIn('id', $zones->lists('zone_category_id')->toArray())->get()->groupBy('group');
	    $zonesByCategory = $zones->groupBy('zone_category_id');
	    $userZoneCounts = $totalZoneCounts = [];
	    
	    foreach ($zones as $zone) {
		    if ($character) { 
			    $zoneItemDisplayIDs = array_unique($zone->itemDisplays->filter(function ($display) use ($classmask, $racemask, $mogslotIDs) {
				    return (in_array($display->mogslot_id, $mogslotIDs) && (($display->restricted_races === null || ($display->restricted_races & $racemask) != 0) && ($display->restricted_classes === null || ($display->restricted_classes & $classmask) != 0)));
				})->lists('id')->toArray());
		    } else {
			    $zoneItemDisplayIDs = array_unique($zone->itemDisplays->lists('id')->toArray());
		    }
		    
		    $totalZoneCounts[$zone->id] = count($zoneItemDisplayIDs);
		    
		    $zoneUserDisplays = $user->userItemDisplays()->whereIn('item_display_id', $zoneItemDisplayIDs)->get();
		    if ($character) {
			    $userZoneCounts[$zone->id] = $zoneUserDisplays->filter(function ($display) use ($classmask, $racemask) {
				    return (($display->restricted_races === null || ($display->restricted_races & $racemask) != 0) && ($display->restricted_classes === null || ($display->restricted_classes & $classmask) != 0));
			    })->count();
		    } else {
			    $userZoneCounts[$zone->id] = $zoneUserDisplays->count();
		    }
	    }
	    
	    if ($character) {
		    $characters = Character::where('id', '<>', $character->id)->where('user_id', '=', $user->id)->orderBy('realm_id', 'ASC')->orderBy('name', 'ASC')->get()->groupBy('realm_id');
	    } else {
		    $characters = Character::where('user_id', '=', $user->id)->orderBy('realm_id', 'ASC')->orderBy('name', 'ASC')->get()->groupBy('realm_id');
	    }
	    
	    return view('items.zone-overview')->with('categories', $zoneCategories)->with('zonesByCategory', $zonesByCategory)->with('userZoneCounts', $userZoneCounts)->with('totalZoneCounts', $totalZoneCounts)->with('selectedCharacter', $character)->with('characters', $characters);
    }
    
    public function setMogslotIcons($mogslotID = null, $iconID = null) {
	    return \App::abort(404);
	    
	    if ($mogslotID) {
		    $_mogslot = Mogslot::find($mogslotID);
		    
		    if ($_mogslot) {
			    $_mogslot->icon_image_id = $iconID;
			    $_mogslot->save();
		    }
	    }
	    
	    $mogslot = Mogslot::where('icon_image_id', '=', null)->first();
	    
	    if (!$mogslot) {
		    echo 'done'; die;
	    }
	    
	    $displays = ItemDisplay::where('transmoggable', '=', 1)->where('mogslot_id', '=', $mogslot->id)->orderBy('bnet_display_id', 'ASC')->get();
	    return view('items.set-icons')->with('mogslot', $mogslot)->with('itemDisplays', $displays);
    }
    
    public function duplicates($selectedCharacterURL = false) {
	    $user = Auth::user();
        
        if ($selectedCharacterURL) {
	        $selectedCharacter = Character::where('user_id', '=', $user->id)->where('url_token', '=', $selectedCharacterURL)->first();
	        
	        if (!$selectedCharacter) {
		        return redirect('items/duplicates');
	        }
        } else {
	        $selectedCharacter = null;
        }
        
        $dupeItems = $user->getDuplicateItems(true, $selectedCharacter);
        
        if ($selectedCharacter) {
	        $characters = Character::where('user_id', '=', $user->id)->orderBy('realm_id', 'ASC')->orderBy('name', 'ASC')->get()->groupBy('realm_id');
        } else {
	        $charIDs = [];
	        
	        foreach ($dupeItems as $dupeItemArr) {
		        foreach ($dupeItemArr as $item) {
			        if ($item->character_id && !in_array($item->character_id, $charIDs)) {
				        $charIDs[] = $item->character_id;
			        }
		        }
	        }
	        
	        $characters = Character::whereIn('id', $charIDs)->orderBy('realm_id', 'ASC')->orderBy('name', 'ASC')->get()->groupBy('realm_id');
        }
                
        return view('items.duplicates')->with('duplicates', $dupeItems)->with('characters', $characters)->with('selectedCharacter', $selectedCharacter);
    }
    
    public function showZoneDisplays($zoneURL) {
	    $zone = Zone::where('url_token', '=', $zoneURL)->first();
	    
	    if (!$zone) {
		    return \App::abort(404);
	    }
	    
	    $zoneSourceTypeIDs = ItemSourceType::where('zone_relevant', '=', 1)->get()->lists('id')->toArray();
	    $itemIDs = array_unique(ItemSource::where('zone_id', '=', $zone->id)->whereIn('item_source_type_id', $zoneSourceTypeIDs)->get()->lists('item_id')->toArray());
	    $displayIDs = array_unique(Item::whereIn('id', $itemIDs)->where('transmoggable', '=', 1)->get()->lists('item_display_id')->toArray());
	    $displays = ItemDisplay::whereIn('id', $displayIDs)->get();
	    
	    return $this->showItemDisplays($displays, false, $itemIDs);
    }
    
    public function showBossDisplays($zoneURL, $bossURL) {
	    $zone = Zone::where('url_token', '=', $zoneURL)->first();
	    
	    if (!$zone) {
		    return \App::abort(404);
	    }
	    
	    $boss = Boss::whereNull('parent_boss_id')->where('zone_id', '=', $zone->id)->where('url_token', '=', $bossURL)->first();
	    
	    if (!$boss) {
		    return \App::abort(404);
	    }
	    
	    $itemIDs = array_unique(ItemSource::where('boss_id', '=', $boss->id)->get()->lists('item_id')->toArray());
	    $displayIDs = array_unique(Item::whereIn('id', $itemIDs)->where('transmoggable', '=', 1)->get()->lists('item_display_id')->toArray());
	    $displays = ItemDisplay::whereIn('id', $displayIDs)->get();
	    
	    return $this->showItemDisplays($displays, false, $itemIDs);
    }
    
    public function showSlot(Request $request, $group, $categoryURL, $mogslotURL) {
	    $category = MogslotCategory::where('group', '=', $group)->where('url_token', '=', $categoryURL)->first();
	    
	    if (!$category) {
		    return \App::abort(404);
	    }
	    
	    $mogslot = Mogslot::where('simple_url_token', '=', $mogslotURL)->where('mogslot_category_id', '=', $category->id)->first();
	    
	    if (!$mogslot) {
		    return \App::abort(404);
	    }
	    
	    $displays = ItemDisplay::where('transmoggable', '=', 1)->where('mogslot_id', '=', $mogslot->id)->orderBy('bnet_display_id', 'ASC')->get();
	    
	    return $this->showItemDisplays($displays, $mogslot);
    }
    
    public function search($query) {
	    $query = str_replace('+', ' ', $query);
	    
	    if (is_numeric($query)) {
		    $items = Item::where('bnet_id', '=', $query)->where('transmoggable', '=', 1)->where('item_display_id', '>', 0)->get();
	    } else {
		    $createdItemSourceType = ItemSourceType::where('label', '=', 'CONTAINED_IN_ITEM')->first();
			$createdFromItemBnetIDs = ItemSource::where('item_source_type_id', '=', $createdItemSourceType->id)->groupBy('bnet_source_id')->get()->lists('bnet_source_id')->toArray();
			$createdItemBnetIDs = [];
		    
		    //search items (by name)
		    $items = Item::where('item_display_id', '>', 0)->where('transmoggable', '=', 1)->search('"' . $query . '"')->get();
			
		    if (!$items->count()) {
			    $createdItemBnetIDs = Item::whereIn('bnet_id', $createdFromItemBnetIDs)->search("'" . $query . "'")->get()->lists('bnet_id')->toArray();
			    
			    if (!count($createdItemBnetIDs)) {
				    $items = Item::where('item_display_id', '>', 0)->where('transmoggable', '=', 1)->search($query)->get();
				} else {
					$searchSource = true;
				}
		    }
		    
		    $searchSource = ($items->count() > 1);
		    
		    if ($searchSource || count($createdItemBnetIDs)) {
			    //search items that are created by another item (by the source item name)
			    if (!count($createdItemBnetIDs)) {
				    $createdItemBnetIDs = Item::whereIn('bnet_id', $createdFromItemBnetIDs)->search($query)->get()->lists('bnet_id')->toArray();
			    }
			    
			    $createdItems = Item::where('item_display_id', '>', 0)->where('transmoggable', '=', 1)->whereHas('itemSources', function ($query) use ($createdItemBnetIDs, $createdItemSourceType) {
				    $query->whereIn('bnet_source_id', $createdItemBnetIDs)->where('item_source_type_id', '=', $createdItemSourceType->id);
			    })->get();
			    
			    $items = $items->merge($createdItems);
			}
		}
		
	    $itemIDs = $items->lists('id')->toArray();
	    
	    $displayIDs = array_unique($items->lists('item_display_id')->toArray());
	    $itemsByDisplay = $items->groupBy('item_display_id');
	    
	    $displays = ItemDisplay::whereIn('id', $displayIDs)->where('transmoggable', '=', 1)->get();
	    
	    // restore search relevance
	    $displays = $displays->sortBy(function ($display) use ($displayIDs) {
		    return array_search($display->id, $displayIDs);
	    });
	    
	    $displays->each(function ($display) use ($itemsByDisplay) {
		    $display->setTempPrimaryItem($itemsByDisplay[$display->id][0]);
	    });
	    
	    return $this->showItemDisplays($displays, false, $itemIDs);
    }
    
    protected function showItemDisplays($displays, $mogslot = false, $priorityItemIDs = []) {
	    $user = Auth::user();
	    
	    $dispIds = $displays->lists('id');
	    $userItems = $user->userItems()->whereIn('item_display_id', $dispIds)->get();
	    $userDisplayIDs = array_unique($userItems->lists('item_display_id')->toArray());
	    $userItemIDs = $userItems->lists('item_id')->toArray();
	    
	    //get item source types
	    $itemIDs = Item::whereIn('item_display_id', $dispIds)->get()->lists('id')->toArray();
		$itemSourceTypeIDs = ItemSource::whereIn('item_id', $itemIDs)->groupBy('item_source_type_id')->get()->lists('item_source_type_id');
		$itemSourceTypes = ItemSourceType::where('url_token', '<>', '')->whereIn('id', $itemSourceTypeIDs)->get();
		
		$mogslotCount = $displays->groupBy('mogslot_id')->count();
		
		if ($mogslotCount > 1) {
			$allowedClassBitmask = ItemDisplay::getAllowedClassBitmaskForDisplays($displays);
		} else {
			$allowedClassBitmask = ($mogslot) ? $mogslot->allowed_class_bitmask : false;			
		}
		
		$allowedRaceBitmask = ItemDisplay::getAllowedRaceBitmaskForDisplays($displays);
		
		if (Item::where('allowable_classes', '>', 0)->whereIn('id', $itemIDs)->get()->count() || $mogslotCount > 1) {
		    $classes = CharClass::orderBy('name', 'ASC')->get();
		    
		    if ($allowedClassBitmask) {
			    $classes = $classes->filter(function ($class) use ($allowedClassBitmask) {
				   $classMask = pow(2, $class->id);
				   return (($classMask & $allowedClassBitmask) !== 0); 
				});
		    }
		} else {
			$classes = false;
		}
		
		$factionRestrictedItemCount = Item::where(function ($query) {
			$query->where('allowable_races', '>', 0);
			$query->orWhere('locked_races', '>', 0);
		})->whereIn('id', $itemIDs)->get()->count()
		
		if ($factionRestrictedItemCount) {
		    $factions = Faction::where('race_bitmask', '>', 0)->orderBy('name', 'ASC')->get();
		    
		    if ($allowedRaceBitmask) {
			    $factions = $factions->filter(function ($faction) use ($allowedRaceBitmask) {
				   return (($faction->race_bitmask & $allowedRaceBitmask) !== 0); 
				});
		    }
		} else {
			$factions = false;
		}
	    
	    return view('items.display-list')->with('mogslot', $mogslot)->with('itemDisplays', $displays)->with('user', $user)->with('userDisplayIDs', $userDisplayIDs)->with('userItemIDs', $userItemIDs)->with('classes', $classes)->with('factions', $factions)->with('itemSourceTypes', $itemSourceTypes)->with('priorityItemIDs', $priorityItemIDs);
    }
    
    public function showAuctions(Request $request) {
	    $user = Auth::user();
	    
	    $classes = CharClass::orderBy('name', 'ASC')->get();;
	    $mogslotCategories = MogslotCategory::all();
	    $mogslotsByCategory = Mogslot::orderBy('simple_label', 'ASC')->get()->groupBy('mogslot_category_id');
	    
	    $class = $request->input('class') ? CharClass::find($request->input('class')) : false;
	    $slotID = $request->input('slot') ?: false;
	    if ($slotID) {
		    $selectedSlot = Mogslot::find($slotID);
		    if ($selectedSlot) {
			    $catID = $selectedSlot->mogslot_category_id;
		    } else {
			    $slotID = false;
		    }
	    } else {
		    $selectedSlot = false;
		    $catID = $request->input('cat') ?: false;
	    }
	    
	    if ($catID) {
		    $selectedCat = MogslotCategory::find($catID);
		    if (!$selectedCat) {
			    $catID = false;
		    }
	    } else {
		    $selectedCat = false;
	    }
	    $auctions = false;
	    $error = false;
	    
	    if ($catID || $slotID) {
		    if ($slotID) {
			    $mogslots = Mogslot::where('id', '=', $slotID)->get();
		    } elseif ($catID) {
			    $mogslots = Mogslot::where('mogslot_category_id', '=', $catID)->get();
		    } else {
			    $mogslots = Mogslot::all();
		    }
			
		    if ($class) {
			    $classmask = pow(2, $class->id);
			    $mogslots = $mogslots->filter(function ($mogslot) use ($classmask) {
					return ($mogslot->allowed_class_bitmask === null || (($classmask & $mogslot->allowed_class_bitmask) !== 0));
			    });
		    } else {
			    $classmask = false;
		    }
		    
		    if (!$mogslots->count()) {
			    return view('items.auctions')->with('classes', $classes)->with('mogslotCategories', $mogslotCategories)->with('mogslots', $mogslotsByCategory)->with('error', 'There was an error with the search. Please try again.')->with('selectedClass', $class)->with('selectedCat', $selectedCat)->with('selectedSlot', $selectedSlot);
		    }
		    
		    if ($classmask) {
			    $dispIds = Item::leftJoin('item_displays', function ($join) {
				    $join->on('items.item_display_id', '=', 'item_displays.id');
			    })->whereIn('item_displays.mogslot_id', $mogslots->lists('id')->toArray())->whereRaw('(items.allowable_classes IS NULL OR items.allowable_classes & ? <> 0) AND items.transmoggable = 1', [$classmask])->groupBy('items.item_display_id')->get()->lists('item_display_id')->toArray();
		    } else {
			    $dispIds = Item::leftJoin('item_displays', function ($join) {
				    $join->on('items.item_display_id', '=', 'item_displays.id');
			    })->whereIn('item_displays.mogslot_id', $mogslots->lists('id')->toArray())->where('items.transmoggable', '=', 1)->groupBy('items.item_display_id')->get()->lists('item_display_id')->toArray();
		    }
		    
		    $auctions = $this->auctionSearch($dispIds)->groupBy('item_display_id');
		    
		    if (!$auctions->count()) {
			    $error = 'No auctions were found matching your search.';
		    }
	    } elseif ($class) {
		    $error = 'Please select an item type.';
	    }
	    
	    return view('items.auctions')->with('classes', $classes)->with('mogslotCategories', $mogslotCategories)->with('mogslots', $mogslotsByCategory)->with('auctions', $auctions)->with('selectedClass', $class)->with('selectedCat', $selectedCat)->with('selectedSlot', $selectedSlot)->with('error', $error);
    }
    
    public function auctionSearch($dispIds) {
	    $user = Auth::user();
	    
	    $userDisplayIDs = DB::table('user_items')->where('user_id', '=', $user->id)->groupBy('item_display_id')->lists('item_display_id');
	    $missingDisplayIDs = array_diff($dispIds, $userDisplayIDs);
	    
	    $realms = $user->getUserAuctionRealms();
	    $auctions = Auction::whereIn('item_display_id', $missingDisplayIDs)->whereIn('realm_id', $realms->lists('id')->toArray())->get();
	    
	    $itemsChecked = [];
	    $itemCounts = [];
	    $uniqueAuctions = [];
	    $auctions = $auctions->sortBy(function ($auction, $key) {
			return $auction->buyout ?: $auction->bid;
		})->filter(function ($auction) use (&$itemsChecked, &$itemCounts, &$uniqueAuctions, $user) {
			$auctionSig = $auction->getSignature();
			if (in_array($auctionSig, $uniqueAuctions)) {
				return false;
			}
			$uniqueAuctions[] = $auctionSig;
			
		    $cacheToken = md5($auction->item_id . '|' . $auction->realm_id);
		    
		    if (!array_key_exists($cacheToken, $itemCounts)) {
			    $itemCounts[$cacheToken] = 0;
		    }
		    $itemCounts[$cacheToken]++;
		    
		    if ($itemCounts[$cacheToken] > 2) {
			    return false;
		    }
		    
		    if (!array_key_exists($cacheToken, $itemsChecked)) {
			    $realmChars = $user->characters()->whereIn('realm_id', $auction->realm->getConnectedRealms()->lists('id')->toArray())->get();
			    
			    $valid = false;
			    foreach ($realmChars as $char) {
					if ($char->canUseItem($auction->item)) {
						$valid = true;
						break;
					}
			    }
			    
			    $itemsChecked[$cacheToken] = $valid;
		    }
		    
		    return $itemsChecked[$cacheToken];
	    });
		
		return $auctions;
    }
    
    public function showDisplay($group, $categoryURL, $mogslotURL, $displayID) {
	    $display = ItemDisplay::find($displayID);
	    
	    if (!$display || !$display->mogslot || !$display->mogslot->mogslotCategory || $display->mogslot->mogslotCategory->url_token != $categoryURL || $display->mogslot->simple_url_token != $mogslotURL || $display->mogslot->mogslotCategory->group != $group) {
		    return \App::abort(404);
	    }
	    
	    $user = Auth::user();
	    
	    $userItems = $user->userItems()->where('item_display_id', '=', $display->id)->get();
	    $userItemIDs = $userItems->lists('item_id')->toArray();
		$displayItems = $display->items()->where('transmoggable', '=', 1)->get();
		
		if (Item::where('allowable_classes', '>', 0)->whereIn('id', $displayItems->lists('id')->toArray())->get()->count()) {
		    $classes = CharClass::orderBy('name', 'ASC')->get();
		    
		    if ($display->mogslot->allowed_class_bitmask) {
			    $classes = $classes->filter(function ($class) use ($display) {
				   $classMask = pow(2, $class->id);
				   return (($classMask & $display->mogslot->allowed_class_bitmask) !== 0); 
				});
		    }
		} else {
			$classes = false;
		}
		
		$unlockedClasses = false;
		if ($userItems->count()) {
			$restrictedClassMask = 0;
			foreach ($displayItems as $item) {
				if (in_array($item->id, $userItemIDs)) {
					if (!$item->allowable_classes) {
						$restrictedClassMask = false;
						break;
					}
					
					$restrictedClassMask = $restrictedClassMask | $item->allowable_classes;
				}
			}
			
			if ($restrictedClassMask) {
				$unlockedClasses = $classes->filter(function ($class) use ($restrictedClassMask) {
					$classMask = pow(2, $class->id);
					return (($restrictedClassMask & $classMask) !== 0);
				})->lists('name')->toArray();
			}
		}
		
		$auctions = (!$userItems->count()) ? $this->auctionSearch([$display->id]) : false;
	    
	    return view('items.display-details')->with('display', $display)->with('userItems', $userItems)->with('displayItems', $displayItems)->with('unlockedClasses', $unlockedClasses)->with('auctions', $auctions);
    }
}
