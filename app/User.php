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
			    
			    if (!$character) {
				    $character = new Character;
				    $character->wow_guid = $charTag;
				    $character->user_id = $this->id;
				    $character->save();
				}
				
				$character->updateCharacterFromDataArray($charData['charInfo']);
			    
			    $scanTime = (@$charData['scanTime']) ? $charData['scanTime'] : 0;
					
				if ($scanTime > $character->last_scanned && $character->realm_id && $character->class_id && $character->race_id) {
					// Queue item import
					$job = (new ImportCharacterItems($character->id, $dataFile->id))->onQueue('med');
					$this->dispatch($job);
					
					$character->last_scanned = $scanTime;
					$character->latest_chardata = json_encode($charData);
				}
				
				$character->save();
			}
	    }
	    
	    if (@$dataFile->import_data['heirlooms'] && is_array($dataFile->import_data['heirlooms']) && count($dataFile->import_data['heirlooms'])) {
		    $heirloomLocation = ItemLocation::where('label', '=', 'heirlooms')->first();
		    
		    foreach ($dataFile->import_data['heirlooms'] as $itemID => $transmoggable) {
		        $item = Item::where('bnet_id', '=', $itemID)->first();
		        
		        if ($item && $item->isTransmoggable()) {
			        $userItem = UserItem::where('user_id', '=', $this->id)->where('item_location_id', '=', $heirloomLocation->id)->where('item_id', '=', $item->id)->first();
			        
			        if (!$userItem) {
				        $userItem = new UserItem;
				        $userItem->user_id = $this->id;
				        $userItem->item_id = $item->id;
				        $userItem->item_display_id = $item->item_display_id;
				        $userItem->item_location_id = $heirloomLocation->id;
				        $userItem->bound = 2;
				        $userItem->save();
					}
			    }
			    
			    $dataFile->incrementResponseData('current', 1);
			    $dataFile->save();
	        }
	    }
    }
    
    public function getOtherCharacters(Character $character, $mailable) {
	    if ($mailable) {
		    return $this->characters()->where('id', '<>', $character->id)->where('faction_id', '=', $character->faction_id)->where('realm_id', '=', $character->realm_id)->get();
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
		    
			foreach ($dupeItems as $displayID => $itemArr) {
				$allQuest = true;
				$involvesCharacter = false;
				foreach ($itemArr as $item) {
					if ($item->item_location_id != $questLocation->id && $item->item_location_id != $heirloomLocation->id) {
						$allQuest = false;
						
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
				
				if (($includeQuestItems && $allQuest) || ($character && !$involvesCharacter)) {
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
