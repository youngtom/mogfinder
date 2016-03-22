<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Character;
use App\Realm;
use App\Item;
use Config;
use App\UserItem;
use DB;
use App\Jobs\ImportCharacterQuestItems;
use Illuminate\Foundation\Bus\DispatchesJobs;

class Character extends Model
{
	use DispatchesJobs;
	
	private static $apiClient = null;
	public $additionalData = [];
	
	public static function boot() {
		parent::boot();
		
		static::saving(function ($character) {
			if (!$character->url_token) {
				$character->url_token = $character->getToken();
			}
			return true;
		});
	}
	
	public function charClass() {
        return $this->belongsTo('App\CharClass', 'class_id');
	}
	
	public function race() {
        return $this->belongsTo('App\Race');
	}
	
	public function realm() {
        return $this->belongsTo('App\Realm');
	}
	
	public function faction() {
        return $this->belongsTo('App\Faction');
	}
	
	public function user() {
        return $this->belongsTo('App\User');
	}
	
    public function items() {
	    return $this->hasMany('App\UserItem');
    }
    
    public function getToken() {
	    return str_slug($this->name . ' ' . $this->realm->name . ' ' . $this->realm->region);
    }
	
	public function canUseItem(Item $item) {
		if ($item->required_level && $this->level < $item->required_level) {
			return false;
		}
		
		if (!($this->charClass && $item->equippableByClass($this->charClass)) || !($this->race && $item->equippableByRace($this->race))) {
			return false;
		}
		
		return true;
	}
	
	public function eligibleForQuestReward(Item $item, $dynamicRewards = false) {
		if ($dynamicRewards && $item->primary_stats) {
			return $this->canUseItem($item) && $this->charClass->eligibleForQuestReward($item);
		} else {
			return $this->canUseItem($item);
		}
	}
	
    public function importBnetData($returnFields = []) {
	 	if (self::$apiClient === null) {
			self::$apiClient = new \App\BnetWowApi(Config::get('settings.bnet_api_key'), Config::get('settings.bnet_api_locale'));  
	    }
	    
	    $data = self::$apiClient->getCharacterData($this->name, $this->realm->name, $this->realm->region, $returnFields);
	    
	    if ($data) {
		    $factionID = $data['faction'] + 1;
		    $this->faction_id = ($factionID) ?: $this->faction_id;
			$this->class_id = ($data['class']) ?: $this->class_id;
			$this->race_id = ($data['race']) ?: $this->race_id;
			$this->level = ($data['level']) ?: $this->level;
			$this->save();
			
			foreach ($returnFields as $field) {
				$this->additionalData[$field] = $data[$field];
			}
			
			return true;
	    } else {
		    return false;
	    }
    }
    
    public function importItemData($dataFileID = null) {
	    if (!$this->latest_chardata) {
		    return false;
	    }
	    
	    \Log::info('Importing item data for character: ' . $this->id);
	    
	    $charData = json_decode($this->latest_chardata, true);
	    
	    $dataFile = ($dataFileID) ? UserDatafile::find($dataFileID) : false;
	    
	    $charItemIDs = [];
        
        $questLocation = ItemLocation::where('label', '=', 'quest')->first();
			    
	    if (@$charData['equipped']) {
		    $equippedLocation = ItemLocation::where('label', '=', 'equipped')->first();
		    
		    foreach ($charData['equipped'] as $equippedItemStr) {
			    list($bound, $xmoggable, $itemLink) = explode('--', $equippedItemStr);
			    
			    $userItem = UserItem::where('character_id', '=', $this->id)->where('item_link', $itemLink)->first();
			    
			    if ($userItem) {
				    $userItem->item_location_id = $equippedLocation->id;
				    $userItem->bound = 1;
				    $userItem->save();
			    } else {
				    $item = Item::findItemFromLink($itemLink);
			    
				    if ($item && $item->isTransmoggable()) {
				        $userItem = $this->addUserItem($item, $itemLink, $equippedLocation);
				    }
			    }
			    
			    if ($userItem) {
				    $charItemIDs[] = $userItem->id;
			    }
			    
			    if ($dataFile) {
				    $dataFile->incrementResponseData('current', 1);
				    $dataFile->save();
			    }
		    }
		}
		
		if (@$charData['items']) {
		    foreach ($charData['items'] as $locationImportTag => $itemArr) {
			    $itemLocation = ItemLocation::where('import_tag', '=', $locationImportTag)->first();
			    
			    if ($itemLocation) {			    
				    foreach ($itemArr as $itemStr) {
					    list($bound, $xmoggable, $itemLink) = explode('--', $itemStr);
					    
					    $userItem = UserItem::where('character_id', '=', $this->id)->where('item_link', $itemLink)->first();
				    
					    if ($userItem) {
						    $userItem->item_location_id = $equippedLocation->id;
						    $userItem->bound = 1;
						    $userItem->save();
					    } else {
						    $item = Item::findItemFromLink($itemLink);
						    
						    if ($item && $item->isTransmoggable()) {
						        if ($this->canUseItem($item)) {
							    	$userItem = $this->addUserItem($item, $itemLink, $itemLocation, $bound);
						        } elseif ($bound != 1) {
							        $alts = $this->user->getOtherCharacters($this, ($bound === 0));
							        
							        $found = false;
							        $alts->each(function ($alt) use ($item, &$found) { 
								        if ($alt->canUseItem($item)) {
											$found = $alt;
											return false;
										}
							        });
							        
							        if ($found) {
								        $userItem = $this->addUserItem($item, $itemLink, $itemLocation, $bound);
							        }
						        }
						    }
						}
											    
					    if ($userItem) {
						    $charItemIDs[] = $userItem->id;
					    }
					    
					    if ($dataFile) {
						    $dataFile->incrementResponseData('current', 1);
						    $dataFile->save();
					    }
				    }
				} else {
					if ($dataFile) {
					    $dataFile->incrementResponseData('current', count ($itemArr));
					    $dataFile->save();
				    }
				}
		    }
		}
		
		DB::table('user_items')->where('item_location_id', '<>', $questLocation->id)->where('character_id', '=', $this->id)->whereNotIn('id', $charItemIDs)->delete();
		$this->latest_chardata = null;
		$this->save();
						
		// Queue quest import
		$job = (new ImportCharacterQuestItems($this->id))->onQueue('low');
	    $this->dispatch($job);
    }
    
    public function importQuestItemData() {
	    if (!isset($this->additionalData['quests'])) {
		    $this->importBnetData(['quests']);
	    }
	    
	    \Log::info('Importing quest data for character: ' . $this->id);
	    
	    if (@$this->additionalData['quests'] && is_array($this->additionalData['quests'])) {
			$questImportToken = md5(serialize($this->additionalData['quests']));
		    
		    if ($questImportToken != $this->quest_import_token) {
			    $quests = array_diff($this->additionalData['quests'], explode(',', $this->quests_imported));
			    
			    if (count($quests)) {
				    $questSourceType = ItemSourceType::where('label', '=', 'REWARD_FOR_QUEST')->first();
				    $questLocation = ItemLocation::where('label', '=', 'quest')->first();
				    
				    foreach ($quests as $questID) {
					    $itemSources = ItemSource::where('bnet_source_id', '=', $questID)->where('item_source_type_id', '=', $questSourceType->id)->get();
					    
					    $itemSources->each(function ($itemSource) use ($questLocation) {
						   $userItem = $this->items()->where('item_id', '=', $itemSource->item_id)->where('item_location_id', '=', $questLocation->id)->first();
						   
						   if (!$userItem && $itemSource->item && $itemSource->item->isTransmoggable()) {
						   		if ($this->eligibleForQuestReward($itemSource->item, $itemSource->dynamic_quest_rewards)) {
									$userItem = $this->addUserItem($itemSource->item, null, $questLocation, null);
								}
						   	}
					    });
				    }
				    
				    $this->quests_imported = implode(',', array_unique(array_merge(explode(',', $this->quests_imported), $quests)));
			    }
			
				$this->quest_import_token = $questImportToken;
				$this->save();
			}
		} else {
			\Log::error('Failed loading bnet quest data for character: ' . $this->id);
		}
    }
    
    public function addUserItem($item, $itemLink, ItemLocation $location, $bound = 1) {
	    $userItem = new UserItem;
        $userItem->user_id = $this->user_id;
        $userItem->item_id = $item->id;
        $userItem->item_display_id = $item->item_display_id;
        $userItem->item_location_id = $location->id;
        $userItem->character_id = $this->id;
        $userItem->item_link = $itemLink;
        $userItem->bound = $bound;
        $userItem->save();
        
        return $userItem;
    }
}
