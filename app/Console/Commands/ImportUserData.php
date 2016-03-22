<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Item;
use App\Character;
use App\Realm;
use App\Race;
use App\Libraries\LuaParser;
use App\BnetWowApi;
use App\UserItem;
use App\ItemLocation;
use App\Faction;
use App\ItemSource;
use App\ItemSourceType;
use DB;

class ImportUserData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:items:import-lua {userid} {filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {     	
        $user = User::find($this->argument('userid'));
        
        if (!$user) {
	        $this->error("Invalid user specified.");
	        return;
        }
        
        $filename = $this->argument('filename');
        if (!$filename || !file_exists($filename)) {
	        $this->error('Please specify a valid file');
	        return;
        }
        
        $parser = new LuaParser($filename);
        $data = $parser->toArray();
        $data = $data['MCCSaved'];
        
        $heirloomLocation = ItemLocation::where('label', '=', 'heirlooms')->first();
        $equippedLocation = ItemLocation::where('label', '=', 'equipped')->first();
        $questLocation = ItemLocation::where('label', '=', 'quest')->first();
        $questSourceType = ItemSourceType::where('label', '=', 'REWARD_FOR_QUEST')->first();
        
        
        foreach ($data['heirlooms'] as $itemID => $transmoggable) {
	        $item = Item::where('bnet_id', '=', $itemID)->first();
	        
	        if ($item && $item->isTransmoggable()) {
		        $userItem = UserItem::where('user_id', '=', $user->id)->where('item_location_id', '=', $heirloomLocation->id)->where('item_id', '=', $item->id)->first();
		        
		        if (!$userItem) {
			        $userItem = new UserItem;
			        $userItem->user_id = $user->id;
			        $userItem->item_id = $item->id;
			        $userItem->item_display_id = $item->item_display_id;
			        $userItem->item_location_id = $heirloomLocation->id;
			        $userItem->bound = 2;
			        $userItem->save();
			        $this->line('Added heirloom: ' . $item->name);
				}
		    }
        }
        
        foreach ($data['chars'] as $charTag => $charData) {
	        $charTagArr = explode(' - ', $charTag);
	        $region = $charTagArr[0];
	        $realmName = $charTagArr[1];
	        $name = $charTagArr[2];
	        $factionName = $charTagArr[3];
	        
	        $character = Character::importBnetData($name, $realmName, $region, ['quests']);
	        	        
	        $realm = Realm::where('name', '=', $realmName)->where('region', '=', $region)->first();
	    
		    if (!$realm) {
			    $realm = new Realm;
			    $realm->name = $realmName;
			    $realm->region = $region;
			    $realm->save();
		    }
	        
	        if (!$character) {
		        $character = Character::where('name', '=', $name)->where('realm_id', '=', $realm->id)->first();
		        
		        if (!$character) {
					$character = new Character;
					$character->name = $name;
					$character->realm_id = $realm->id;
					$character->level = 0;
					$character->faction_id = ($character->faction_id) ?: @Faction::where('name', '=', $factionName)->first()->id;
					$character->save();
			    }
	        }
	        
	        if (!$character->user_id) {
		        $character->user_id = $user->id;
		        $character->save();
		    }
		    
		    $data['chars'][$charTag]['characterObj'] = $character;
	    }
        
        foreach ($data['chars'] as $charTag => $charData) {
	        $character = $charData['characterObj'];
	        
	        $this->info('----------------------------------------------');
	        $this->info($charTag);
	        $this->info('----------------------------------------------');
	        
		    if ($character && @$character->additionalData['quests'] && is_array($character->additionalData['quests'])) {
			    $questImportToken = md5(serialize($character->additionalData['quests']));
			    
			    if ($questImportToken != $character->quest_import_token) {
				    $questDiffArr = array_diff($character->additionalData['quests'], explode(',', $character->quests_imported));
				    
				    foreach ($questDiffArr as $questID) {
					    $itemSources = ItemSource::where('bnet_source_id', '=', $questID)->where('item_source_type_id', '=', $questSourceType->id)->get();
					    
					    $itemSources->each(function ($itemSource) use ($character, $questLocation) {
						   $userItem = $character->items()->where('item_id', '=', $itemSource->item_id)->where('item_location_id', '=', $questLocation->id)->first();
						   
						   if (!$userItem && $itemSource->item && $itemSource->item->isTransmoggable()) {
						   		if ($character->eligibleForQuestReward($itemSource->item, $itemSource->dynamic_quest_rewards)) {
									$userItem = $character->addUserItem($itemSource->item, null, $questLocation, null);
								  	$this->info('Quest Item: ' . $userItem->item->name . ' added.');
								} else {
									$this->error('Quest Item: ' . $itemSource->item->name . ' not eligible.');
								}
						   	} elseif ($userItem) {
							   	$this->line('Quest Item: ' . $userItem->item->name . ' already exists, skipping.');
						   	}
					    });
				    }
				    
				    $character->quest_import_token = $questImportToken;
				    $character->quests_imported = implode(',', $character->additionalData['quests']);
				    $character->save();
			    }
		    }
		    
		    $scanTime = (@$charData['scanTime']) ? $charData['scanTime'] : 0;
		    
		    if ($scanTime > $character->last_scanned) {
			    //UserItem::where('user_id', '=', $user->id)->where('item_location_id', '<>', $questLocation->id)->where('character_id', '=', $character->id)->delete();
			    $charItemIDs = [];
			    
			    if (@$charData['equipped']) {
				    foreach ($charData['equipped'] as $equippedItemStr) {
					    list($bound, $xmoggable, $itemLink) = explode('--', $equippedItemStr);
					    
					    $userItem = UserItem::where('character_id', '=', $character->id)->where('item_link', $itemLink)->first();
					    
					    if ($userItem) {
						    $userItem->location_id = $equippedLocation->id;
						    $userItem->bound = 1;
					    } else {
						    $item = Item::findItemFromLink($itemLink);
					    
						    if ($item && $item->isTransmoggable()) {
						        $userItem = $character->addUserItem($item, $itemLink, $equippedLocation);
						        //$this->line('- Item added: ' . $item->name);
						    } elseif (!$item) {
							    $this->error('Item not found: ' . $itemLink);
						    }
					    }
					    
					    if ($userItem) {
						    $charItemIDs[] = $userItem->id;
					    }
				    }
				}
				
				if (@$charData['items']) {
				    foreach ($charData['items'] as $locationImportTag => $itemArr) {
					    $itemLocation = ItemLocation::where('import_tag', '=', $locationImportTag)->first();
					    
					    if (!$itemLocation) {
						    $itemLocation = new ItemLocation;
						    $itemLocation->import_tag = $locationImportTag;
						    $itemLocation->save();
						    $this->error('Location not found, created: ' . $locationImportTag);
					    }
					    
					    foreach ($itemArr as $itemStr) {
						    list($bound, $xmoggable, $itemLink) = explode('--', $itemStr);
						    
						    $userItem = UserItem::where('character_id', '=', $character->id)->where('item_link', $itemLink)->first();
					    
						    if ($userItem) {
							    $userItem->location_id = $equippedLocation->id;
							    $userItem->bound = 1;
						    } else {
							    $item = Item::findItemFromLink($itemLink);
							    
							    if ($item && $item->isTransmoggable()) {
							        if ($character->canUseItem($item)) {
								    	$userItem = $character->addUserItem($item, $itemLink, $itemLocation, $bound);
								    	//$this->line('- Item added: ' . $item->name);
							        } elseif ($bound != 1) {
								        if ($bound == 0) {
									        $alts = $user->characters()->where('id', '<>', $character->id)->where('faction_id', '=', $character->faction_id)->where('realm_id', '=', $character->realm_id)->get();
								        } else {
									        $alts = $user->characters()->where('id', '<>', $character->id)->get();
								        }
								        
								        $this->line('Checking if item is usable on another character: ' . $item->name);
								        
								        $found = false;
								        $alts->each(function ($alt) use ($item, &$found) { 
									        if ($alt->canUseItem($item)) {
												$found = $alt;
												return false;
											}
								        });
								        
								        if ($found) {
									        $this->info('-- Item usable by another character: ' . $item->name . ' - ' . $found->name . ' - ' . $found->realm->name);
									        $userItem = $character->addUserItem($item, $itemLink, $itemLocation, $bound);
								        } else {
									        $this->error('- Item not usable: ' . $item->name);
								        }
							        } else {
								        $this->error('- Soulbound Item not added: ' . $item->name . ' (' . $item->id . ')');
							        }
							    } elseif (!$item) {
								    $this->error('Item not found: ' . $itemLink);
								}
							}
												    
						    if ($userItem) {
							    $charItemIDs[] = $userItem->id;
						    }
					    }
				    }
				}
				
				DB::table('user_items')->where('item_location_id', '<>', $questLocation->id)->where('character_id', '=', $character->id)->whereNotIn('id', $charItemIDs)->delete();
				
				$character->last_scanned = $scanTime;
				$character->save();
			}
        }
    }
}
