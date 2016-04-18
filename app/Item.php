<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Config;
use App\Race;
use App\Faction;
use Sofa\Eloquence\Eloquence;
use App\ItemDisplay;

class Item extends Model
{
	use Eloquence;
	use \App\Http\Traits\FileHandler;
	
	private static $apiClient = null;
	protected $searchableColumns = ['name', 'bnet_id'];
	
	public static function boot() {
		parent::boot();
		
		self::saved(function ($item) {
			if ($item->itemDisplay && ($item->isDirty('item_display_id') || $item->isDirty('allowable_classes') || $item->isDirty('allowable_races'))) {
				$item->itemDisplay->updateRestrictions();
			}
		});
		
		self::deleted(function ($item) {
			$display = ItemDisplay::find($item->item_display_id);
			
			if ($display) {
				$display->updateRestrictions();
			}
		});
	}
	
	public function inventoryType() {
        return $this->belongsTo('App\InventoryType');
	}
	
	public function itemSubtype() {
        return $this->belongsTo('App\ItemSubtype');
	}
	
	public function itemDisplay() {
        return $this->belongsTo('App\ItemDisplay');
	}
	
	public function itemContext() {
        return $this->belongsTo('App\ItemContext');
	}
	
	public function isTransmoggable() {
		return $this->transmoggable;
	}
	
	public function itemSources() {
		return $this->hasMany('App\ItemSource');
	}
	
	public function getBnetData() {
		if (self::$apiClient === null) {
			self::$apiClient = new \App\BnetWowApi(Config::get('settings.bnet_api_key'), Config::get('settings.bnet_api_locale'));
	    }
	    
	    if ($this->itemContext) {
		    $itemContextLabel = (stristr($this->itemContext->label, 'trade-skill')) ? 'trade-skill' : $this->itemContext->label;
		    $data = self::$apiClient->getItemData($this->bnet_id, $itemContextLabel, $this->bonus);
	    } else {
		    $data = self::$apiClient->getItemData($this->bnet_id);
	    }
	    
	    return $data;
	}
	
    public static function importBnetData($itemID) {
	    if (self::$apiClient === null) {
			self::$apiClient = new \App\BnetWowApi(Config::get('settings.bnet_api_key'), Config::get('settings.bnet_api_locale'));
	    }
	    
	    $data = self::$apiClient->getItemData($itemID);
	    
	    $itemsCreated = [];
	    
	    if ($data) {
		    if (count($data['availableContexts']) && trim($data['availableContexts'][0]) && ($data['itemClass'] == 2 || $data['itemClass'] == 4)) {
			    foreach ($data['availableContexts'] as $contextLabel) {
				    $contextLabelOrig = $contextLabel;
				    if ($contextLabel == 'trade-skill') {
					    $contextLabel = ($data['itemClass'] == 2) ? 'trade-skill-weapon' : 'trade-skill-armor';
				    }
				    
				    $context = ItemContext::where('label', '=', $contextLabel)->first();
				    
				    if (!$context) {
					    $context = new ItemContext;
					    $context->label = $contextLabel;
					    $context->save();
				    }
				    
				    $bonuses = ($context->bonuses) ? explode(',', $context->bonuses) : [];

					do {
						$bonus = array_pop($bonuses);
						$contextItemData = self::$apiClient->getItemData($itemID, $contextLabelOrig, $bonus);
						
						if ($contextItemData) {
							$itemsCreated[] = self::storeItemData($contextItemData, $context, $bonus);
						}
					} while (count($bonuses));
			    }
		    } else {
			    $itemsCreated[] = self::storeItemData($data);
		    }
	    } else {
		    return $data;
	    }
	    
	    return $itemsCreated;
    }
    
    private static function storeItemData($data, ItemContext $context = null, $bonus = null) {
	    $contextID = ($context) ? $context->id : null;
	    $item = Item::where('bnet_id', '=', $data['id'])->where('item_context_id', '=', $contextID)->where('bonus', '=', $bonus)->first();
	    
	    if (!$item) {
		    $item = new Item;
		    $item->bnet_id = $data['id'];
		    $item->item_context_id = $contextID;
		    if ($bonus) { 
			    $item->bonus = $bonus;
		    } elseif ($contextID && $data['bonusLists']) {
			    $item->bonus = implode(',', $data['bonusLists']);
			} else {
				$item->bonus = null;
			}
	    }
	    
	    $item->imported_from_bnet = 1;
	    
	    $item->name = $data['name'];
	    $item->item_bind = $data['itemBind'];
	    $item->buy_price = $data['buyPrice'];
	    $item->sell_price = $data['sellPrice'];
	    
	    //save type, subtype, and inventory type
	    $type = ItemType::where('bnet_id', '=', $data['itemClass'])->first();
	    
	    if (!$type) {
		    $type = new ItemType;
		    $type->bnet_id = $data['itemClass'];
		    $type->save();
	    }
	    
	    $item->item_type_id = $type->id;
	    
	    $subtype = ItemSubtype::where('bnet_id', '=', $data['itemSubClass'])->where('item_type_id', '=', $type->id)->first();
	    
	    if (!$subtype) {
		    $subtype = new ItemSubtype;
		    $subtype->bnet_id = $data['itemSubClass'];
		    $subtype->item_type_id = $type->id;
		    $subtype->save();
	    }
	    
	    $item->item_subtype_id = $subtype->id;
	    
	    if ($data['inventoryType']) {
		    $invType = InventoryType::where('id', '=', $data['inventoryType'])->first();
		    
		    if (!$invType) {
			    $invType = new InventoryType;
			    $invType->id = $data['inventoryType'];
			    $invType->save();
		    }
		    
		    $item->inventory_type_id = $invType->id;
		}
		
		if ($data['displayInfoId']) {
			$invTypeID = ($invType->parent_inventory_type_id) ?: $item->inventory_type_id;
			$display = ItemDisplay::where('bnet_display_id', '=', $data['displayInfoId'])->where('item_subtype_id', '=', $item->item_subtype_id)->where('inventory_type_id', '=', $invTypeID)->first();
			
			if (!$display) {
				$display = new ItemDisplay;
				$display->bnet_display_id = $data['displayInfoId'];
				$display->item_subtype_id = $item->item_subtype_id;
				$display->inventory_type_id = $invTypeID;
				$display->transmoggable = $item->transmoggable;
				$display->save();
			}
			
			$item->item_display_id = $display->id;
	    } else {
		    $item->item_display_id = null;
	    }
	    
	    $item->equippable = ($data['equippable']) ? 1 : 0;
	    $item->auctionable = ($data['isAuctionable']) ? 1 : 0;
	    $item->item_level = $data['itemLevel'];
	    $item->quality = $data['quality'];
	    $item->bnet_faction_id = (@$data['minFactionId']) ?: null;
	    $item->reputation_level = (@$data['minReputation']) ?: null;
	    $item->required_level = $data['requiredLevel'];
	    $item->allowable_classes = (@$data['allowableClasses'] && is_array($data['allowableClasses'])) ? self::getBitmaskFromIDArray($data['allowableClasses']) : null;
	    $item->allowable_races = (@$data['allowableRaces'] && is_array($data['allowableRaces'])) ? self::getBitmaskFromIDArray($data['allowableClasses']) : null;
	    
	    $item->save();
	    
	    if ($data['itemSource'] && $data['itemSource']['sourceId'] && $data['itemSource']['sourceType']) {
		    $sourceType = ItemSourceType::where('label', '=', $data['itemSource']['sourceType'])->first();
		    
		    if (!$sourceType) {
			    $sourceType = new ItemSourceType;
			    $sourceType->label = $data['itemSource']['sourceType'];
			    $sourceType->save();
		    }
		    
		    $source = ItemSource::where('item_id', '=', $item->id)->where('bnet_source_id', '=', $data['itemSource']['sourceId'])->where('item_source_type_id', '=', $sourceType->id)->first();
		    
		    if (!$source) {
			    $source = new ItemSource;
			    $source->item_id = $item->id;
			    $source->bnet_source_id = $data['itemSource']['sourceId'];
			    $source->item_source_type_id = $sourceType->id;
			    $source->import_source = 'bnet';
			    $source->save();
		    }
	    }
	    
	    return $item;
    }
    
    public function equippableByClass($class) {
	    if (!$class) {
		    return false;
	    }
	    
	    $classMask = pow(2, $class->id);
	    
	    if ($this->allowable_classes && ($classMask & $this->allowable_classes) == 0) {
		    return false;
	    }
	    
	    if ($this->inventoryType && !$this->inventoryType->usable_by_all_classes && $this->itemSubtype && $this->itemSubtype->usable_by_classes_bitmask && ($classMask & $this->itemSubtype->usable_by_classes_bitmask) == 0) {
		    return false;
	    }
	    
	    return true;
    }
    
    public function equippableByRace(Race $race) {	    
	    if (!$race) {
		    return false;
	    }
	    
	    return !($this->allowable_races && (pow(2, $race->id) & $this->allowable_races) == 0);
    }
    
    public function getWowheadMarkup() {
		return ($this->bonus) ? 'bonus=' . $this->bonus : '';
	}
	
	public function getRestrictedClasses() {
		if (!$this->allowable_classes) {
			return false;
		}
		
		$mask = $this->allowable_classes;
		$classes = CharClass::orderBy('name', 'ASC')->get();
		
		$restrictedClasses = $classes->filter(function ($class) use ($mask) {
			return ((pow(2, $class->id) & $mask) !== 0);
		});
		
		return $restrictedClasses;
	}
	
	public function getRestrictedFactions() {
		if (!$this->allowable_races) {
			return false;
		}
		
		$mask = $this->allowable_races;
		$factions = Faction::where('race_bitmask', '>', 0)->orderBy('name', 'ASC')->get();
		
		$restrictedFactions = $factions->filter(function ($faction) use ($mask) {
			return (($faction->race_bitmask & $mask) !== 0);
		});
		
		return $restrictedFactions;
	}
    
    public static function getBitmaskFromIDArray($arr) {
	    iF (!count($arr)) {
		    return null;
	    }
	    
	    $arr = array_unique($arr);
	    
	    $sum = 0;
	    foreach ($arr as $val) {
		    $sum += pow(2, $val);
	    }
	    return $sum;
    }
    
    public static function parseItemLink($link) {
	    $linkArr = explode('|h', $link);
	    $linkArr = explode(':', $linkArr[0]);
	    
	    preg_match('/(.*)\|h\[(?P<name>.*)\]\|h\|r/', $link, $matches);
	    
	    $out = [
			'id' => $linkArr[1],
			'enchantID' => $linkArr[2],
			'bonuses' => array_slice($linkArr, 14, $linkArr[13]),
			'name' => stripslashes($matches['name'])
	    ];
	    
	    return $out;
    }
    
    public static function findItemFromLink($link) {
	    $linkData = self::parseItemLink($link);
	    
	    $items = Item::where('bnet_id', '=', $linkData['id'])->get();
	    
	    if ($items->count() == 1) {
		    return $items->first();
	    } elseif ($items) {
		    $bonuses = $linkData['bonuses'];
		    $filtered = $items->filter(function ($item) use ($bonuses) {
			    if (in_array($item->bonus, $bonuses)) {
				    return true;
			    }
		    });
		    
		    if ($filtered->count()) {
			    return $filtered->first();
		    } else {
			    $item = Item::where('bnet_id', '=', $linkData['id'])->where('bonus', '=', null)->first();
			    return ($item) ?: false;
		    }
	    } else {
		    return false;
	    }
    }
}
