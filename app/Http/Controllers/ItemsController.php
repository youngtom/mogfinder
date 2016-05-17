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
		    $characters = Character::where('id', '<>', $character->id)->where('user_id', '=', $user->id)->where('level', '>=', 10)->orderBy('realm_id', 'ASC')->orderBy('name', 'ASC')->get()->groupBy('realm_id');
	    } else {
		    $characters = Character::where('user_id', '=', $user->id)->where('level', '>=', 10)->orderBy('realm_id', 'ASC')->orderBy('name', 'ASC')->get()->groupBy('realm_id');
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
		    $characters = Character::where('id', '<>', $character->id)->where('user_id', '=', $user->id)->where('level', '>=', 10)->orderBy('realm_id', 'ASC')->orderBy('name', 'ASC')->get()->groupBy('realm_id');
	    } else {
		    $characters = Character::where('user_id', '=', $user->id)->where('level', '>=', 10)->orderBy('realm_id', 'ASC')->orderBy('name', 'ASC')->get()->groupBy('realm_id');
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
    
    public function showItem($bnetID) {
	    $items = Item::where('bnet_id', '=', $bnetID)->get();
	    
	    if (!$items->count()) {
		    return \App::abort(404);
	    }
	    
	    $itemName = $items->first()->name;
	    $itemIDs = $items->lists('id')->toArray();
	    
	    if ($items->where('transmoggable', 1)->count()) {
		    $displayIDs = array_unique($items->lists('item_display_id')->toArray());
	    } else {
		    $sources = ItemSource::whereIn('source_item_id', $itemIDs)->get();
		    
		    if ($sources->count()) {
			    $itemIDs = array_unique($sources->lists('item_id')->toArray());
			    $displayIDs = array_unique(Item::whereIn('id', $itemIDs)->where('transmoggable', '=', 1)->get()->lists('item_display_id')->toArray());
		    } else {
			    $displayIDs = [];
		    }
	    }
		
	    $displays = ItemDisplay::whereIn('id', $displayIDs)->get();
	    
	    return $this->showItemDisplays($displays, false, $itemIDs)->with('headerText', 'Item: <em>' . $itemName . '</em>');
    }
    
    public function showVendorDisplays($bnetID) {
	    $vendorSourceType = ItemSourceType::where('label', '=', 'VENDOR')->first();
	    $sourceItemIDs = ItemSource::where('item_source_type_id', '=', $vendorSourceType->id)->where('bnet_source_id', '=', $bnetID)->get()->lists('item_id')->toArray();
	    
	    if (!count($sourceItemIDs)) {
		    return \App::abort(404);
	    }
	    
	    $source = ItemSource::where('item_source_type_id', '=', $vendorSourceType->id)->where('bnet_source_id', '=', $bnetID)->first();
	    
	    if ($source) {
		    $sourceLabel = $source->label;
		    
		    if ($source->zone) {
			    $sourceLabel .= ' (' . $source->zone->name . ')';
		    }
	    }
	    
	    $displayIDs = array_unique(Item::whereIn('id', $sourceItemIDs)->where('transmoggable', '=', 1)->get()->lists('item_display_id')->toArray());
	    $displays = ItemDisplay::whereIn('id', $displayIDs)->get();
	    
	    return $this->showItemDisplays($displays, false, $sourceItemIDs)->with('headerText', 'Vendor: <em>' . $sourceLabel . '</em>');
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
	    
	    return $this->showItemDisplays($displays, false, $itemIDs)->with('headerText', 'Zone: <em>' . $zone->name . '</em>');
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
	    
	    return $this->showItemDisplays($displays, false, $itemIDs)->with('headerText', 'Boss: <em>' . $boss->name . ' (' . $zone->name . ')</em>');
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
	    
	    return $this->showItemDisplays($displays, $mogslot)->with('headerText', ucwords($mogslot->mogslotCategory->group) . ': <em>' . $mogslot->label . '</em>');
    }
    
    public function searchHints($query) {
	    $q = '"' . $query . '"';
	    $results = [];
	    $resultPriority = ['item', 'boss', 'zone', 'vendor'];
	    
	    //search items
	    $items = Item::search($q)->get();
	    
	    $items = $items->filter(function ($item) {
		    if ($item->transmoggable == 0 || !$item->item_display_id) {
			    $sources = ItemSource::where('source_item_id', '=', $item->id)->get();
			    return ($sources->count()) ? true : false;
		    } else {
			    return true;
		    }
	    });
	    
	    if ($items->count()) {
		    $itemBnetIDs = [];
		    $results['item'] = [];
		    
		    foreach ($items as $item) {
			    if (!in_array($item->bnet_id, $itemBnetIDs)) {
				    $itemBnetIDs[] = $item->bnet_id;
				    
				    $results['item'][] = [
					    'type' => 'item',
					    'value' => $item->name,
					    'id' => $item->id,
					    'link' => route('item', [$item->bnet_id]),
					    'linkClass' => 'q' . $item->quality
				    ];
			    }
		    }
	    }
	    
	    //search zones
	    $zones = Zone::search($q)->get();
	    
	    if ($zones->count()) {
		    $results['zone'] = [];	
		    foreach ($zones as $zone) {
			    $results['zone'][] = [
				    'type' => 'zone',
				    'value' => $zone->name,
				    'id' => $zone->id,
				    'link' => route('zone', [$zone->url_token]),
				    'linkClass' => 'zone'
			    ];
		    }
		}
	    
	    //search bosses
	    $bosses = Boss::search($q)->whereNull('parent_boss_id')->get();
	    $encounterIDs = [];
	    
	    if ($bosses->count()) {
		    $results['boss'] = [];		    
		    foreach ($bosses as $boss) {				    
			    $results['boss'][] = [
				    'type' => 'boss',
				    'value' => $boss->name,
				    'id' => $boss->id,
				    'link' => route('boss', [$boss->zone->url_token, $boss->url_token]),
				    'linkClass' => 'boss'
			    ];
		    }
	    }
	    
	    //search source labels
	    $sources = ItemSource::search($q)->get();
	    $vendorBnetIDs = [];
	    
	    if ($sources->count()) {
		    foreach ($sources as $source) {
			    if ($source->itemSourceType->label == 'VENDOR') {
				    if (!array_key_exists('vendor', $results)) {
					    $results['vendor'] = [];
				    }
				    
				    if (!in_array($source->bnet_source_id, $vendorBnetIDs)) {
					    $results['vendor'][] = [
						    'type' => 'vendor',
						    'value' => $source->label,
						    'id' => $source->id,
						    'link' => route('vendor', [$source->bnet_source_id]),
						    'linkClass' => 'vendor'
					    ];
					    $vendorBnetIDs[] = $source->bnet_source_id;
					}
			    }
		    }
	    }
	    
	    $out = [];
	    $maxResults = 10;
	    
	    while (count($out) < $maxResults && count($results)) {
		    foreach ($results as $type => &$resultArr) {
			    if (count($results[$type])) {
				    $out[] = array_pop($resultArr);
			    } else {
				    unset($results[$type]);
			    }
			    
			    if (count($out) >= $maxResults) {
				    break;
			    }
		    }
	    }
	    
	    usort($out, function ($a1, $a2) use ($resultPriority) {
		    return array_search($a1['type'], $resultPriority) > array_search($a2['type'], $resultPriority);
	    });
	    
	    //workaround for typeahead choking on results = maxresults
	    if (count($out) == $maxResults) {
		    $out[] = ['value' => null];
	    }
	    
	    return \Response::json($out);
    }
    
    public function search($query) {
	    $query = str_replace('+', ' ', $query);
	    $q = '"' . $query . '"';
	    $items = collect();
	    
	    if (is_numeric($query)) {
		    $bnetIDItems = Item::where('bnet_id', '=', $query)->get();
		    
		    foreach ($bnetIDItems as $item) {
			    if ($item->transmoggable == 1 && $item->item_display_id) {
				    $items->push($item);
			    } else {
				    $sourceItemIDs = ItemSource::where('source_item_id', '=', $item->id)->get()->lists('item_id')->toArray();
				    
				    if (count($sourceItemIDs)) {
					    $sourceItems = Item::whereIn('id', $sourceItemIDs)->where('item_display_id', '>', 0)->where('transmoggable', '=', 1)->get();
					    
					    $items = $items->merge($sourceItems);
				    }
			    }
		    }
	    } else {
			//search for items
			$itemsByName = Item::where('item_display_id', '>', 0)->where('transmoggable', '=', 1)->search($q)->get();
			$items = $items->merge($itemsByName);
			
			//search for items from related items
			$allRelatedItemIDs = ItemSource::whereNotNull('source_item_id')->get(['source_item_id'])->lists('source_item_id')->toArray();
			$relatedItemIDs = Item::whereIn('id', $allRelatedItemIDs)->search($q)->get()->lists('id')->toArray();
			
			if (count($relatedItemIDs)) {
				$itemIDs = ItemSource::whereIn('source_item_id', $relatedItemIDs)->get()->lists('item_id')->toArray();
				$itemsFromItems = Item::whereIn('id', $itemIDs)->where('item_display_id', '>', 0)->where('transmoggable', '=', 1)->get();
				$items = $items->merge($itemsFromItems);
			}
		    
		    //search for bosses
			$bosses = ($items->count()) ? Boss::where('name', '=', $query)->get() : Boss::search($q)->get();
			
			if ($bosses->count()) {
				$itemIDs = [];
				foreach ($bosses as $boss) {
					$itemIDs = array_merge($itemIDs, ItemSource::where('boss_id', '=', $boss->id)->get()->lists('item_id')->toArray());
				}
				
				if (count($itemIDs)) {
					$bossItems = Item::whereIn('id', $itemIDs)->where('transmoggable', '=', 1)->where('item_display_id', '>', 0)->get();
					$items = $items->merge($bossItems);
				}
			}
			
			//search for zones
			$zones = ($items->count()) ? Zone::where('name', '=', $query)->get() : Zone::search($q)->get();
			
			if ($zones->count()) {
				$itemIDs = [];
				$zoneSourceTypeIDs = ItemSourceType::where('zone_relevant', '=', 1)->get()->lists('id')->toArray();
				
				foreach ($zones as $zone) {
				    $itemIDs = array_merge($itemIDs, ItemSource::where('zone_id', '=', $zone->id)->whereIn('item_source_type_id', $zoneSourceTypeIDs)->get()->lists('item_id')->toArray());
				}
				
				if (count($itemIDs)) {
					$zoneItems = Item::whereIn('id', $itemIDs)->where('transmoggable', '=', 1)->where('item_display_id', '>', 0)->get();
					$items = $items->merge($zoneItems);
				}
			}
			
			//search source labels
			$sourceItemIDs = ($items->count()) ? ItemSource::where('label', '=', $query)->get()->lists('item_id')->toArray() : ItemSource::search($q)->get()->lists('item_id')->toArray();;
			
			if (count($sourceItemIDs)) {
				$sourceItems = Item::whereIn('id', $sourceItemIDs)->where('item_display_id', '>', 0)->where('transmoggable', '=', 1)->get();
				$items = $items->merge($sourceItems);
			}
		}
		
	    $itemIDs = $items->lists('id')->toArray();
	    
	    $displayIDs = array_unique($items->lists('item_display_id')->toArray());
	    $itemsByDisplay = $items->groupBy('item_display_id');
	    
	    $displays = ItemDisplay::whereIn('id', $displayIDs)->where('transmoggable', '=', 1)->get();
	    
	    if ($displays->count() == 1) {
		    $display = $displays->first();
		    return redirect()->route('display', [$display->mogslot->mogslotCategory->group, $display->mogslot->mogslotCategory->url_token, $display->mogslot->simple_url_token, $display->id]);
	    }
	    
	    // restore search relevance
	    $displays = $displays->sortBy(function ($display) use ($displayIDs) {
		    return array_search($display->id, $displayIDs);
	    });
	    
	    return $this->showItemDisplays($displays, false, $itemIDs)->with('headerText', 'Search results for: <em>' . $query . '</em>')->with('search', true);
    }
    
    protected function showItemDisplays($displays, $mogslot = false, $priorityItemIDs = []) {
	    $user = Auth::user();
	    
	    $dispIds = $displays->lists('id');
	    $userItems = $user->userItems()->whereIn('item_display_id', $dispIds)->get();
	    $userDisplayIDs = array_unique($userItems->lists('item_display_id')->toArray());
	    $userItemIDs = $userItems->lists('item_id')->toArray();
	    
	    //get item source types
	    $itemIDs = Item::whereIn('item_display_id', $dispIds)->where('transmoggable', '=', 1)->get()->lists('id')->toArray();
		$itemSourceTypeIDs = ItemSource::whereIn('item_id', $itemIDs)->groupBy('item_source_type_id')->get()->lists('item_source_type_id');
		$itemSourceTypes = ItemSourceType::where('ordering', '>', 0)->whereIn('id', $itemSourceTypeIDs)->orderBy('ordering', 'ASC')->get();
		
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
		})->whereIn('id', $itemIDs)->get()->count();
		
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
    
    public function legacyDisplays() {
	    $displays = ItemDisplay::where('legacy', '=', 1)->where('transmoggable', '=', 1)->get();
	    return $this->showItemDisplays($displays);
    }
    
    public function legacyAuctions() {
	    $displays = ItemDisplay::where('legacy', '=', 1)->where('transmoggable', '=', 1)->get();
	    $auctions = $this->auctionSearch($displays->lists('id')->toArray())->groupBy('item_display_id');
		    
	    if (!$auctions->count()) {
		    $error = 'No auctions were found matching your search.';
	    } else {
		    $error = false;
	    }
	    
	    $classes = CharClass::orderBy('name', 'ASC')->get();
	    $mogslotCategories = MogslotCategory::all();
	    $mogslotsByCategory = Mogslot::orderBy('simple_label', 'ASC')->get()->groupBy('mogslot_category_id');
	    
	    return view('items.auctions')->with('classes', $classes)->with('mogslotCategories', $mogslotCategories)->with('mogslots', $mogslotsByCategory)->with('auctions', $auctions)->with('selectedClass', false)->with('selectedCat', false)->with('selectedSlot', false)->with('error', $error);
    }
    
    public function showAuctions(Request $request) {
	    $user = Auth::user();
	    
	    $classes = CharClass::orderBy('name', 'ASC')->get();
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
