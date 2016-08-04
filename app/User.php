<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use App\UserItem;
use App\UserDatafile;
use App\Character;
use App\CharClass;
use App\Race;
use App\Realm;
use DB;
use App\Jobs\ImportCharacterItems;
use App\Jobs\ImportCharacterQuestItems;
use Illuminate\Foundation\Bus\DispatchesJobs;

class User extends Authenticatable
{
	use DispatchesJobs;
	
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    
    public function userItems() {
	    return $this->hasMany('App\UserItem');
    }
    
    public function userItemDisplays() {
	    return $this->hasMany('App\UserItemDisplay');
    }
    
    public function characters() {
	    return $this->hasMany('App\Character');
    }
    
    public function importUserData($dataFileID) {
	    $dataFile = UserDatafile::findOrFail($dataFileID);
	    
	    \Log::info('Importing user data for user: ' . $this->id);
	    
	    foreach ($dataFile->import_data['chars'] as $charTag => $charData) {
		    if ($charTag) {
			    $character = Character::where('wow_guid', '=', $charTag)->where('user_id', '=', $this->id)->first();
			    
			    if (!$character && $charData['charInfo']) {
				    $character = new Character;
				    $character->wow_guid = $charTag;
				    $character->user_id = $this->id;
				    $character->save();
				}
				
				if ($character) {
					$character->updateCharacterFromDataArray($charData['charInfo']);					
				}
			}
	    }
	    
	    if (@$dataFile->import_data['appearances'] && is_array($dataFile->import_data['appearances']) && count($dataFile->import_data['appearances'])) {
		    foreach ($dataFile->import_data['appearances'] as $appKey => $itemArr) {
		        $appearanceID = str_replace('app', '', $appKey);
		        $appearanceIDs = [$appearanceID, null];
		        
		        foreach ($itemArr as $itemStr) {
			        if (stristr($itemStr, '|')) {
				        list($itemID, $bonusStr) = explode('|', $itemStr);
				        $bonuses = explode(':', $bonusStr);
				        $item = Item::where('bnet_id', '=', $itemID)->whereIn('appearance_id', $appearanceIDs)->whereIn('bonus', $bonuses)->first();
				    } else {
					    $itemId = $itemStr;
					    $item = Item::where('bnet_id', '=', $itemID)->whereIn('appearance_id', $appearanceIDs)->where('bonus', '=', null)->first();
				    }
				    
				    if ($item) {
					    $userItem = UserItem::where('user_id', '=', $this->id)->where('item_id', '=', $item->id)->first();
			        
				        if (!$userItem) {
					        $userItem = new UserItem;
					        $userItem->user_id = $this->id;
					        $userItem->item_id = $item->id;
					        $userItem->item_display_id = $item->item_display_id;
					        $userItem->bound = 2;
					        $userItem->save();
						}
				    } else {
					    \Log::error('Item not found (' . $appKey . ') - ' . $itemStr);
				    }
				    
				    DB::table('user_datafiles')->where('id', '=', $dataFile->id)->increment('progress_current');
		        }
	        }
	    }
    }
    
    public function getOtherCharacters(Character $character, $mailable) {
	    if ($mailable && $character->realm) {
		    return $this->characters()->where('id', '<>', $character->id)->where('faction_id', '=', $character->faction_id)->whereIn('realm_id', $character->realm->getConnectedRealms()->lists('id')->toArray())->get();
        } else {
	        return $this->characters()->where('id', '<>', $character->id)->get();
        }
    }
    
    public function getDuplicateItems($includeQuestItems = false, $character = null) {
		$questLocation = ItemLocation::where('label', '=', 'quest')->first();
		
	    if ($includeQuestItems) {
		    $displayIDs = UserItem::where('user_id', '=', $this->id)->groupBy('item_display_id')->havingRaw('count(*) > 1')->lists('item_display_id')->toArray();
		} else {
			$displayIDs = UserItem::where('user_id', '=', $this->id)->where('item_location_id', '<>', $questLocation->id)->groupBy('item_display_id')->havingRaw('count(*) > 1')->lists('item_display_id')->toArray();
		}
	    
	    $dupeItems = $this->userItems->filter(function ($item) use ($displayIDs, $includeQuestItems, $questLocation) {
		    return ($includeQuestItems || !$includeQuestItems && $item->item_location_id != $questLocation->id) && in_array($item->item_display_id, $displayIDs);
	    })->groupBy('item_display_id');
	    
	    if ($includeQuestItems || $character) {
		    $heirloomLocation = ItemLocation::where('label', '=', 'heirlooms')->first();
		    $equippedLocation = ItemLocation::where('label', '=', 'equipped')->first();
		    
		    $irrelevantLocations = [$questLocation->id, $heirloomLocation->id, $equippedLocation->id];
		    
			foreach ($dupeItems as $displayID => $itemArr) {
				$allIrrelevant = true;
				$involvesCharacter = false;
				foreach ($itemArr as $item) {
					if (!in_array($item->item_location_id, $irrelevantLocations)) {
						$allIrrelevant = false;
						
						if ($character) {
							if ($character->id == $item->character_id) {
								$involvesCharacter = true;
								break;
							}
						} else {
							break;
						}
					}
				}
				
				if (($includeQuestItems && $allIrrelevant) || ($character && !$involvesCharacter)) {
					unset($dupeItems[$displayID]);
				}
			}
	    }
	    
	    return $dupeItems;
    }
    
    public function getUserAuctionRealms() {
	    $realms = collect();
	    $realmIDs = Character::where('user_id', '=', $this->id)->groupBy('realm_id')->get()->lists('realm_id');
	    
	    foreach ($realmIDs as $realmID) {
		    $realm = Realm::find($realmID);
		    
		    $realms = $realms->merge($realm->getConnectedRealms());
	    }
	    
	    return $realms;
    }
}
