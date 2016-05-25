<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Boss;
use Config;
use Sofa\Eloquence\Eloquence;
use App\ItemDisplay;

class Zone extends Model
{
	use Eloquence;
	
	private static $apiClient = null;
	protected $fillable = ['bnet_id'];
	protected $searchableColumns = ['name'];
	
	public function bosses() {
		return $this->hasMany('App\Boss');
	}
	
	public function encounter() {
		return $this->hasMany('App\Boss')->whereNull('parent_boss_id');
	}
	
	public function itemDisplays() {
		return $this->belongsToMany('App\ItemDisplay');
	}
	
	public function category() {
		return $this->belongsTo('App\ZoneCategory');
	}
	
	public function updateDisplays() {
		$zoneSourceTypeIDs = ItemSourceType::where('zone_relevant', '=', 1)->get()->lists('id')->toArray();
		$itemIDs = ItemSource::whereIn('item_source_type_id', $zoneSourceTypeIDs)->where('zone_id', '=', $this->id)->groupBy('item_id')->get()->lists('item_id')->toArray();
		$displayIDs = Item::whereIn('id', $itemIDs)->where('transmoggable', '=', 1)->groupBy('item_display_id')->get()->lists('item_display_id')->toArray();
		$this->itemDisplays()->sync($displayIDs);
	}
	
	public static function importBnetZoneData() {
		if (self::$apiClient === null) {
			self::$apiClient = new \App\BnetWowApi(Config::get('settings.bnet_api_key'), Config::get('settings.bnet_api_locale'));
	    }
	    
	    $data = self::$apiClient->getZoneData();
	    $zones = @$data['zones'];
	    
	    if (!$zones) {
		    return false;
	    }
	    
	    foreach ($zones as $zoneArr) {
		    $zone = self::firstOrCreate(['bnet_id' => $zoneArr['id']]);
		    
		    if (!$zone->name) {
			    $zone->name = $zoneArr['name'];
			    $zone->url_token = $zoneArr['urlSlug'];
		    }
		    $zone->is_raid = ($zoneArr['isRaid']) ? 1 : 0;
		    $zone->is_dungeon = ($zoneArr['isDungeon']) ? 1 : 0;
		    $zone->expansion = $zoneArr['expansionId'];
		    $zone->available_modes = (@$zoneArr['availableModes'] && is_array($zoneArr['availableModes'])) ? implode(',', $zoneArr['availableModes']) : null;
		    
		    if (@$zoneArr['location'] && @$zoneArr['location']['id']) {
			    $parentZone = self::where('bnet_id', '=', $zoneArr['location']['id'])->first();
			    
			    if (!$parentZone) {
				    $parentZone = new static;
				    $parentZone->bnet_id = $zoneArr['location']['id'];
				    $parentZone->name = $zoneArr['location']['name'];
				    $parentZone->url_token = str_slug($zoneArr['location']['name']);
				    $parentZone->save();
			    }
			    
			    $zone->parent_zone_id = $parentZone->id;
		    }
		    
		    foreach ($zoneArr['bosses'] as $bossArr) {
			    $boss = Boss::firstOrCreate(['bnet_id' => $bossArr['id'], 'parent_boss_id' => null]);
			    
			    if (!$boss->name) {
				    $boss->name = $bossArr['name'];
				    $boss->url_token = $bossArr['urlSlug'];
				}
			    $boss->zone_id = $zone->id;
			    
			    if (@$bossArr['npcs'] && count($bossArr['npcs'])) {
				    foreach ($bossArr['npcs'] as $npcArr) {
					    $npc = Boss::firstOrCreate(['bnet_id' => $npcArr['id'], 'parent_boss_id' => $boss->id]);
					    
					    if (!$npc->name) {
						    $npc->name = $npcArr['name'];
						    $npc->url_token = $npcArr['urlSlug'];
						}
					    $npc->zone_id = $zone->id;
					    $npc->save();
				    }
			    }
			    
			    $boss->save();
		    }
		    
		    $zone->save();
	    }
	}
}
