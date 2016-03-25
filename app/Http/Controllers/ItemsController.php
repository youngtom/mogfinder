<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Auth;
use App\Character;
use App\ItemDisplay;
use App\Mogslot;
use App\MogslotCategory;
use App\CharClass;
use App\Item;
use App\ItemSource;
use App\ItemSourceType;

class ItemsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index() {
	    $user = Auth::user();
	    
	    $mogslotCategories = MogslotCategory::all()->groupBy('group');
	    
	    $userMogslotCounts = [];
	    $mogslots = Mogslot::all();
	    
	    foreach ($mogslots as $mogslot) {
		    $userMogslotCounts[$mogslot->id] = count($user->userItems()->whereIn('item_display_id', $mogslot->itemDisplays->lists('id'))->get()->groupBy('item_display_id'));
	    }
	    
	    return view('items.overview')->with('categories', $mogslotCategories)->with('userMogslotCounts', $userMogslotCounts);
    }
    
    public function setMogslotIcons($mogslotID = null, $iconID = null) {
	    return App::abort(404);
	    
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
    
    public function showSlot($group, $categoryURL, $mogslotURL) {
	    $category = MogslotCategory::where('group', '=', $group)->where('url_token', '=', $categoryURL)->first();
	    
	    if (!$category) {
		    return App::abort(404);
	    }
	    
	    $mogslot = Mogslot::where('simple_url_token', '=', $mogslotURL)->where('mogslot_category_id', '=', $category->id)->first();
	    
	    if (!$mogslot) {
		    return App::abort(404);
	    }
	    
	    $mogslotIDs = [$mogslot->id];
	    /*
	    if ($mogslot->cosmetic) {
		    $cosmeticSlots = Mogslot::where('cosmetic', '=', 1)->where('id', '<>', $mogslot->id)->where('inventory_type_id', '=', $mogslot->inventory_type_id)->get();
		    
		    foreach ($cosmeticSlots as $slot) {
			    $mogslotIDs[] = $slot->id;
		    }
		}
		*/
	    
	    $displays = ItemDisplay::where('transmoggable', '=', 1)->whereIn('mogslot_id', $mogslotIDs)->orderBy('bnet_display_id', 'ASC')->get();
	    
	    return $this->showItemDisplays($displays, $mogslot);
    }
    
    public function search($query) {
	    $query = str_replace('+', ' ', $query);
	    
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
	    $userItems = $user->userItems()->whereIn('item_display_id', $displays->lists('id'))->get();
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
	    
	    return view('items.display-list')->with('mogslot', $mogslot)->with('itemDisplays', $displays)->with('user', $user)->with('userDisplayIDs', $userDisplayIDs)->with('userItemIDs', $userItemIDs)->with('classes', $classes)->with('itemSourceTypes', $itemSourceTypes)->with('priorityItemIDs', $priorityItemIDs);
    }
}
