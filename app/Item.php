<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Config;
use App\Race;
use App\Faction;
use Sofa\Eloquence\Eloquence;
use App\ItemDisplay;
use App\WowheadCache;
use App\Currency;

class Item extends Model
{
	use Eloquence;
	use \App\Http\Traits\FileHandler;
	
	private static $apiClient = null;
	protected $searchableColumns = ['name', 'bnet_id'];
	
	public static function boot() {
		parent::boot();
		
		self::saved(function ($item) {
			if ($item->itemDisplay && ($item->isDirty('item_display_id') || $item->isDirty('allowable_classes') || $item->isDirty('allowable_races') || $item->isDirty('locked_races'))) {
				$item->itemDisplay->updateRestrictions();
			}
			
			if ($item->itemDisplay && $item->isDirty('transmoggable')) {
				$item->itemDisplay->updateTransmoggable();
			}
		});
		
		self::deleted(function ($item) {
			$display = ItemDisplay::find($item->item_display_id);
			
			if ($display) {
				$display->updateRestrictions();
				$display->updateTransmoggable();
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
	
	public function updateBnetBonusData() {
		if (self::$apiClient === null) {
			self::$apiClient = new \App\BnetWowApi(Config::get('settings.bnet_api_key'), Config::get('settings.bnet_api_locale'));
	    }
	    
	    if (!$this->item_context_id) {
		    return false;
	    }
	    
	    $context = ItemContext::find($this->item_context_id);
	    
	    if (!$context) {
		    return false;
	    }
	    
	    $contextLabel = (stristr($context->label, 'trade-skill')) ? 'trade-skill' : $context->label;
	    
	    $data = self::$apiClient->getItemData($this->bnet_id, $contextLabel);
	    
	    if ($data && @$data['bonusLists']) {
		    $item->bonus = implode(',', $data['bonusLists']);
		    $item->save();
		    return $item->bonus;
	    } else {
		    return false;
	    }
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
	    
	    if (!$item->item_type_id) {
		    $type = ItemType::where('bnet_id', '=', $data['itemClass'])->first();
		    
		    if (!$type) {
			    $type = new ItemType;
			    $type->bnet_id = $data['itemClass'];
			    $type->save();
		    }
		    
		    $item->item_type_id = $type->id;
		}
	    
	    if (!$item->item_subtype_id) {
		    $subtype = ItemSubtype::where('bnet_id', '=', $data['itemSubClass'])->where('item_type_id', '=', $type->id)->first();
		    
		    if (!$subtype) {
			    $subtype = new ItemSubtype;
			    $subtype->bnet_id = $data['itemSubClass'];
			    $subtype->item_type_id = $type->id;
			    $subtype->save();
		    }
		    
		    $item->item_subtype_id = $subtype->id;
	    }
	    
	    if (!$item->inventory_type_id && $data['inventoryType']) {
		    $invType = InventoryType::where('id', '=', $data['inventoryType'])->first();
		    
		    if (!$invType) {
			    $invType = new InventoryType;
			    $invType->id = $data['inventoryType'];
			    $invType->save();
		    }
		    
		    $item->inventory_type_id = $invType->id;
		}
		
		if (!$item->item_display_id && $data['displayInfoId']) {
			$invTypeID = (@$invType && $invType->parent_inventory_type_id) ?: $item->inventory_type_id;
			$display = ItemDisplay::where('bnet_display_id', '=', $data['displayInfoId'])->where('item_subtype_id', '=', $item->item_subtype_id)->where('inventory_type_id', '=', $invTypeID)->first();
			
			if (!$display) {
				$display = new ItemDisplay;
				$display->bnet_display_id = $data['displayInfoId'];
				$display->item_subtype_id = $item->item_subtype_id;
				$display->inventory_type_id = $invTypeID;
				//$display->transmoggable = $item->transmoggable;
				
				$mogslot = Mogslot::where('inventory_type_id', '=', $display->inventory_type_id)->where('item_subtype_id', '=', $display->item_subtype_id)->first();
				
				if ($mogslot) {
					$display->mogslot_id = $mogslot->id;
				}
				
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
	    $item->allowable_races = (@$data['allowableRaces'] && is_array($data['allowableRaces'])) ? self::getBitmaskFromIDArray($data['allowableRaces']) : null;
	    
	    $item->save();
	    
	    /*
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
	    */
	    
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
	    
	    return !($this->getAllowedRaceMask() && (pow(2, $race->id) & $this->getAllowedRaceMask()) == 0);
    }
    
    public function getWowheadMarkup() {
		return ($this->bonus) ? 'bonus=' . $this->bonus : '';
	}
	
	public function getSourceDataHTML($includeHidden = false) {
		$_sources = collect();
		$itemSourcesByType = ($includeHidden) ? $this->itemSources->groupBy('item_source_type_id') : $this->itemSources()->where('hidden', '=', 0)->get()->groupBy('item_source_type_id');
		$extraSourceSlots = max(4 - $itemSourcesByType->count(), 0);
		
		foreach ($itemSourcesByType as $itemSourceTypeID => $sources) {
			$sourceType = $sources->first()->itemSourceType;
			$sourceTypeSources = $sourceType->getSourceDataHTML($this, $sources, $extraSourceSlots + 1);
			$_sources = $_sources->merge($sourceTypeSources);
			$extraSourceSlots = max($extraSourceSlots - $sourceTypeSources->count() + 1, 0);
		}
		return implode(', ', $_sources->toArray());
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
		if (!$this->getAllowedRaceMask()) {
			return false;
		}
		
		$mask = $this->getAllowedRaceMask();
		$factions = Faction::where('race_bitmask', '>', 0)->orderBy('name', 'ASC')->get();
		
		$restrictedFactions = $factions->filter(function ($faction) use ($mask) {
			return (($faction->race_bitmask & $mask) !== 0);
		});
		
		return $restrictedFactions;
	}
	
	public function getAllowedRaceMask() {
		if ($this->allowable_races && $this->locked_races) {
			return $this->allowable_races & $this->locked_races;
		} elseif ($this->allowable_races) {
			return $this->allowable_races;
		} elseif ($this->locked_races) {
			return $this->locked_races;
		} else {
			return null;
		}
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
    
    public function isItemToken() {
	    return ((!$this->equippable && $this->item_type_id == 16 && $this->item_subtype_id == 108 && $this->quality == 4) || $this->bnet_id == 47242);
    }
    
    // wowhead source import functions
    
	public function importWowheadSources($types = false) {
		$html = WowheadCache::getItemHtml($this->bnet_id);
		
		if (stristr($html, '<b style="color: red">This item\'s source is no longer available/removed.</b>')) {
			\Log::info('Removing non-legacy sources for item: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
			
			$legacySource = ItemSource::where('item_id', '=', $this->id)->where('item_source_type_id', '=', 17)->first();
			
			if (!$legacySource) {
				$legacySource = new ItemSource;
				$legacySource->item_id = $this->id;
				$legacySource->item_source_type_id = 17;
				$legacySource->import_source = 'wowheadImport';
				$legacySource->save();
			}
			
			ItemSource::where('item_id', '=', $this->id)->where('item_source_type_id', '<>', 17)->delete();
			
			return;
		} elseif (stristr($html, '<b style="color: red">This item is not available to players.</b>')) {
			$this->transmoggable = 0;
			$this->save();
			\Log::info('Setting unavailable item as untransmoggable: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
			return;
		}
		
		if ($html) {
			$sourceData = $this->_processWowheadHtml($html, $types);
			
			if (!$sourceData || !count($sourceData)) {
				\Log::info('Source data not found for item: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
				return false;
			}
			
			foreach ($sourceData as $type => $dataArr) {
				switch ($type) {
					case 'npc|dropped-by':
						$this->_processWowheadDropData($dataArr);
						break;
					case 'object|contained-in-object':
						$this->_processWowheadObjectData($dataArr);
						break;
					case 'spell|created-by-spell':
						$this->_processWowheadCreateBySpellData($dataArr);
						break;
					case 'npc|sold-by':
						$this->_processWowheadVendorData($dataArr);
						break;
					case 'quest|reward-from-q':
						$this->_processWowheadQuestData($dataArr);
						break;
					case 'item|contained-in-item':
						$this->_processWowheadContainedInItemData($dataArr);
						break;
					case 'item|created-by-item':
						$this->_processWowheadCreatedByItemData($dataArr);
						break;
				}
			}
		}
	}
	
	private function _processWowheadDropData($dataArr) {
		$filteredDataArr = [];
		
		$_bossIDs = [];
		$allBosses = true;
		foreach ($dataArr as $data) {
			$npcID = $data['id'];
			
			$boss = Boss::where('bnet_id', '=', $npcID)->first();
			
			$include = true;
			if ($boss) {
				$_bossID = $boss->encounter()->id;
				
				if (in_array($_bossID, $_bossIDs)) {
					$include = false;
				} else {
					$_bossIDs[] = $_bossID;
				}
			} else {
				$allBosses = false;
			}
			
			if ($include && @$data['location'] && ($boss || $data['count'] > 0)) {
				$filteredDataArr[] = $data;
			}
		}
		$dataArr = $filteredDataArr;
		
		if (!count($dataArr)) {
			return false;
		}
		
		if (count($dataArr) == 1 || $allBosses) {
			$valid = 0;
		        
	        if (count($dataArr) > 1) {
		        \Log::info('Creating multiple boss drops for item: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
	        }
	        
			foreach ($dataArr as $data) {
		        $zoneBnetID = @$data['location'][0];
		        $npcID = $data['id'];
		        
		        if (!$zoneBnetID) {
			        \Log::info('Location info not available for item drop: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
		        } elseif (count($data['location']) != 1) {
			        \Log::info('Multiple locations for NPC (' . $npcID . ') for item: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
		        } else {
			        $zone = Zone::where('bnet_id', '=', $zoneBnetID)->first();
			        
			        if (!$zone) {
				        \Log::info('Zone (' . $zoneBnetID . ') not found for item: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
			        } else {
				        $boss = Boss::where('bnet_id', '=', $npcID)->first();
				        $boss = ($boss) ? $boss->encounter() : false;
				        if ($boss) {
					        $bossIDArr = array_unique(array_merge([$npcID], Boss::where('id', '=', $boss->id)->orWhere('parent_boss_id', '=', $boss->id)->get()->lists('bnet_id')->toArray()));
				        } else {
					        $bossIDArr = [$npcID];
				        }
				        
				        if (!$boss && ($zone->is_raid || $zone->is_dungeon)) {
					        \Log::info('Boss (' . $npcID . ') not found for item: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
				        }
				        
				        $sourceTypeID = ($boss) ? 21 : 4;
				        $source = ItemSource::where('item_id', '=', $this->id)->where('item_source_type_id', '=', $sourceTypeID)->whereIn('bnet_source_id', $bossIDArr)->first();
				        
				        if (!$source) {
					        $source = new ItemSource;
					        $source->item_id = $this->id;
					        $source->item_source_type_id = $sourceTypeID;
					        $source->bnet_source_id = $npcID;
					        $source->import_source = 'wowheadImport';
				        }
				        
				        $source->boss_id = ($boss) ? $boss->id : null;
				        $source->zone_id = $zone->id;
				        $source->save();
				        $valid++;
				    }
				    
				    if ($valid) {
					    ItemSource::where('item_id', '=', $this->id)->where('item_source_type_id', '=', 15)->delete();
				    }
			    }
		    }
	        return true;
        } else { //verify that item drops from a single zone
	        $zoneID = false;
	        foreach ($dataArr as $data) {
		        if (@$data['location'] && @$data['location'][0]) {
			        $zoneBnetID = $data['location'][0];
			        
			        if ($zoneID !== false && $zoneBnetID != $zoneID) {
				        $zoneID = false;
				        
				        if (!$this->itemSources->count()) {
					        $source = new ItemSource;
					        $source->item_id = $this->id;
					        $source->item_source_type_id = 3;
					        $source->import_source = 'wowheadImport';
					        $source->save();
				        }
				        return true;
			        }
			        
			        $zoneID = $zoneBnetID;
			    }
	        }
	        
	        if ($zoneID) {
				$zone = Zone::where('bnet_id', '=', $zoneID)->first();
		        
		        if (!$zone) {
			        \Log::info('Zone (' . $zoneID . ') not found for item: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
			        return false;
		        }
		        
		        $source = ItemSource::where('item_id', '=', $this->id)->where('item_source_type_id', '=', 15)->where('bnet_source_id', '=', $zoneID)->first();
			        
		        if (!$source) {
			        $source = new ItemSource;
			        $source->item_id = $this->id;
			        $source->item_source_type_id = 15;
				    $source->bnet_source_id = $zoneID;
			        $source->zone_id = $zone->id;
			        $source->import_source = 'wowheadImport';
			        $source->save();
		        }
		        return true;
	        }
        }
        
        return false;
	}
	
	private function _processWowheadObjectData($dataArr) {
		if (count($dataArr) == 1) {
	        $data = $dataArr[0];
	        
	        if (!@$data['location']) {
		        \Log::info('Location info not available for item in object: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
		        return false;
	        }
	        
	        if (count($data['location']) == 1) {
		        $zoneBnetID = $data['location'][0];
		        $objectID = $data['id'];
		        
		        $zone = Zone::where('bnet_id', '=', $zoneBnetID)->first();
		        
		        if ($zone && $objectID) {
			        $source = ItemSource::where('item_id', '=', $this->id)->where('item_source_type_id', '=', 6)->first();
			        
			        if (!$source) {
				        $source = new ItemSource;
				        $source->item_id = $this->id;
				        $source->item_source_type_id = 6;
				        $source->bnet_source_id = $objectID;
				        $source->zone_id = $zone->id;
				        $source->import_source = 'wowheadImport';
				        $source->save();
			        }					
				}
	        } else {
		        \Log::info('Item contained in object in ' . count($data['location']) . ' zones: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
	        }
	    } else {
		    \Log::info('Item contained in multiple objects: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
	    }
	}
	
	private function _processWowheadCreateBySpellData($dataArr) {
		if (count($dataArr) != 1) {
			\Log::info('Item created by multiple spells: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
			return false;
		}
		
		$data = $dataArr[0];
		$spellID = $data['id'];
		
		$sourceTypeID = (@$data['skill']) ? 11 : 1;
		
		$source = ItemSource::where('item_id', '=', $this->id)->whereIn('item_source_type_id', [11, 1])->first();
			        
	    if (!$source) {
	        $source = new ItemSource;
	        $source->item_id = $this->id;
	        $source->import_source = 'wowheadImport';
	    }
	    
	    $source->item_source_type_id = $sourceTypeID;
	    $source->bnet_source_id = $spellID;
	    $source->save();
	}
	
	private function _processWowheadVendorData($dataArr) {
		foreach ($dataArr as $data) {
			$vendorID = $data['id'];
			
			if (!@$data['location']) {
				\Log::info('NPC (' . $vendorID . ') location not found for item: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
			}
			
			if (@$data['location'] && count($data['location']) == 1) {
				$zoneBnetID = $data['location'][0];
				
				$zone = Zone::where('bnet_id', '=', $zoneBnetID)->first();
	        
		        if (!$zone) {
			        \Log::info('Zone (' . $zoneBnetID . ') not found for item: ' . $this->id . ' (bnet id: ' . $this->bnet_id . ')');
		        }
			} else {
				$zone = false;
			}
			
			$factionID = false;
			if (@$data['react']) {
				$alliance = $horde = false;
				if ($data['react'][0] == 1) {
					$alliance = true;
				}
				
				if ($data['react'][1] == 1) {
					$horde = true;
				}
				
				if ($alliance && !$horde) {
					$factionID = 1;
				} elseif (!$alliance && $horde) {
					$factionID = 2;
				}
			}
			
			$source = ItemSource::where('item_id', '=', $this->id)->where('item_source_type_id', '=', 2)->where('bnet_source_id', '=', $vendorID)->first();
			
			if (!$source) {
				$source = new ItemSource;
		        $source->item_id = $this->id;
		        $source->item_source_type_id = 2;
		        $source->bnet_source_id = $vendorID;
		        $source->import_source = 'wowheadImport';
			}
			
			$source->zone_id = ($zone) ? $zone->id : $source->zone_id;
			$source->faction_id = ($factionID) ?: null;
			$source->label = (@$data['name']) ?: null;
			
			if (@$data['cost']) {
				if (count($data['cost']) > 3) {
					die('Item: ' . $this->bnet_id . ' has mismatched currencies - ' . $data['cost']);
				}
				$source->gold_cost = (@$data['cost'][0]) ?: $source->gold_cost;
				
				$currencyInfo = @$data['cost'][1];
				if ($currencyInfo) {
					if (count($currencyInfo) > 1) {
						die('Item: ' . $this->bnet_id . ' has multiple currencies - ' . $data['cost']);
					}
					
					$currencyID = ($currencyInfo[0][0]) ?: false;
					
					if ($currencyID) {
						$currency = Currency::where('bnet_id', '=', $currencyID)->first();
						
						if (!$currency) {
							$currency = new Currency;
							$currency->bnet_id = $currencyID;
							$currency->save();
						}
						
						$source->currency_id = $currency->id;
						$source->currency_amount = $currencyInfo[0][1];
					}
				}
				
				$source->item_currency_info = (@$data['cost'][2]) ? json_encode($data['cost'][2]) : null;
			}
			
			$source->save();
		}
	}
	
	private function _processWowheadQuestData($dataArr) {
		foreach ($dataArr as $questArr) {
			$questID = $questArr['id'];
			
			$itemSource = ItemSource::where('item_source_type_id', '=', 7)->where('bnet_source_id', '=', $questID)->where('item_id', '=', $this->id)->first();
	    
		    if (!$itemSource) {
			    $itemSource = new ItemSource;
			    $itemSource->item_id = $this->id;
			    $itemSource->item_source_type_id = 7;
			    $itemSource->bnet_source_id = $questID;
			    $itemSource->import_source = 'wowheadImport';
		    }
		    
		    if (@$questArr['side'] && ($questArr['side'] == 1 || $questArr['side'] == 2)) {
			    $itemSource->faction_id = $questArr['side'];
		    }
		    $itemSource->save();
		}
	}
	
	private function _processWowheadContainedInItemData($dataArr) {
		$ignoreItems = [127854, 127853, 127855, 122486, 122485, 122484, 118531, 118530, 118529]; // WoD raid loot boxes
		
		foreach ($dataArr as $data) {
			$itemBnetID = $data['id'];
			
			$itemSource = ItemSource::where('item_source_type_id', '=', 12)->where('bnet_source_id', '=', $itemBnetID)->where('item_id', '=', $this->id)->first();
	    
		    if (!$itemSource && !in_array($itemBnetID, $ignoreItems)) {
			    $sourceItem = Item::where('bnet_id', '=', $itemBnetID)->first();
			    
			    $itemSource = new ItemSource;
			    $itemSource->item_id = $this->id;
			    $itemSource->item_source_type_id = 12;
			    $itemSource->bnet_source_id = $itemBnetID;
			    $itemSource->source_item_id = ($sourceItem) ? $sourceItem->id : null;
			    $itemSource->import_source = 'wowheadImport';
			    $itemSource->save();
		    }
		}
	}
	
	private function _processWowheadCreatedByItemData($dataArr) {
		foreach ($dataArr as $data) {
			$itemBnetID = $data['id'];
			
			$itemSource = ItemSource::where('item_source_type_id', '=', 16)->where('bnet_source_id', '=', $itemBnetID)->where('item_id', '=', $this->id)->first();
	    
		    if (!$itemSource) {
			    $sourceItem = Item::where('bnet_id', '=', $itemBnetID)->first();
			    
			    $itemSource = new ItemSource;
			    $itemSource->item_id = $this->id;
			    $itemSource->item_source_type_id = 16;
			    $itemSource->bnet_source_id = $itemBnetID;
			    $itemSource->source_item_id = ($sourceItem) ? $sourceItem->id : null;
			    $itemSource->import_source = 'wowheadImport';
			    $itemSource->save();
		    }
		}
	}
	
	private function _processWowheadHtml($html, $types = false) {
		$types = ($types) ?: ['npc|dropped-by', 'object|contained-in-object', 'quest|reward-from-q', 'npc|sold-by', 'item|contained-in-item', 'spell|created-by-spell', 'item|created-by-item'];
		
		$matches = [];
		$json = false;
		$arr = explode('new Listview', $html);
		
		$out = [];
		
		foreach ($types as $dropTypeStr) {
			list($dropType, $dropSubtype) = explode('|', $dropTypeStr);
			
			foreach ($arr as $str) {
				$str = preg_replace('/[\n\r]/', '', $str);
				$str = preg_replace('!\s+!', ' ', $str);
				
				preg_match_all('/\(\{template\: \'' . preg_quote($dropType) . '\', id\: \'' . preg_quote($dropSubtype) . '\', (.+), data\: (?P<data>\[(.+)\])\}\);/', $str, $matches);
				if (@$matches['data'][0]) {
					$json = $matches['data'][0];
					
					$json = preg_replace('/,(")?(count)(?(1)\1|)/', ',"count"', $json);
					$json = preg_replace('/,(")?(stock)(?(1)\1|)/', ',"stock"', $json);
					$json = preg_replace('/,(")?(cost)(?(1)\1|)/', ',"cost"', $json);
					$json = preg_replace('/,(")?(outof)(?(1)\1|)/', ',"outof"', $json);
					$json = preg_replace('/,(")?(personal_loot)(?(1)\1|)/', ',"personal_loot"', $json);
					$json = preg_replace('/(")?(undefined)(?(1)\1|)/', '"undefined"', $json);
					
					$jsonArr = json_decode($json, true);
					
					if (!$jsonArr) {
						die('Malformed json for item: ' . $this->bnet_id . ': ' . $json);
					}
					
					$out[$dropTypeStr] = $jsonArr;
					
					break;
				}
			}
		}
		
		return $out;
	}
}
