<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\FileUpload;
use Config;
use Sofa\Eloquence\Eloquence;
use App\Zone;
use App\ItemSource;

class ItemDisplay extends Model
{
	use Eloquence;
	use \App\Http\Traits\FileHandler;
	
	protected $primaryItemOverride = null;
	protected $searchableColumns = ['items.name'];
	protected $allowedClassBitmask = -1;
	protected $allowedRaceBitmask = -1;
	
	public function updateRestrictions($save = true) {
		$classmask = $racemask = 0;
		foreach ($this->items as $item) {
			if ($item->isTransmoggable()) {
				$classmask = ($item->allowable_classes && $classmask !== null) ? $classmask | $item->allowable_classes : null;
				$racemask = ($item->getAllowedRaceMask() && $racemask !== null) ? $racemask | $item->getAllowedRaceMask() : null;
			
				if ($classmask === null && $racemask === null) {
					break;
				}
			}
		}
		
		$this->restricted_classes = $classmask ?: null;
		$this->restricted_races = $racemask ?: null;
		
		if ($save) {
			$this->save();
		}
	}
	
	public function updateTransmoggable($save = true) {
		$transmoggable = false;
		
		foreach ($this->items as $item) {
			if ($item->isTransmoggable()) {
				$transmoggable = true;
				break;
			}
		}
		
		$this->transmoggable = ($transmoggable) ? 1 : 0;
		
		if ($save) {
			$this->save();
		}
	}
	
	public function updateZones() {
		$zoneSourceTypeIDs = ItemSourceType::where('zone_relevant', '=', 1)->get()->lists('id')->toArray();
		$itemIDs = Item::where('item_display_id', '=', $this->id)->where('transmoggable', '=', 1)->get()->lists('id')->toArray();
		$zoneIDs = ItemSource::whereNotNull('zone_id')->whereIn('item_source_type_id', $zoneSourceTypeIDs)->whereIn('item_id', $itemIDs)->groupBy('zone_id')->get()->lists('zone_id')->toArray();
		$this->zones()->sync($zoneIDs);
	}
	
	public function items() {
		return $this->hasMany('App\Item');
	}
	
	public function transmoggableItems() {
		return $this->hasMany('App\Item')->where('transmoggable', '=', 1)->orderBy('bnet_id', 'ASC');
	}
	
	public function primaryItem() {
		return $this->hasOne('App\Item', 'item_display_id', 'primary_item_id');
	}
	
	public function mogslot() {
		return $this->belongsTo('App\Mogslot');
	}
	
	public function zones() {
		return $this->belongsToMany('App\Zone');
	}
	
	public function getURL($base) {
		return url('/' . $base . '/' . $this->mogslot->mogslotCategory->group . '/' . $this->mogslot->mogslotCategory->url_token . '/' . $this->mogslot->simple_url_token . '/' . $this->id);
	}
	
	public function getPrimaryItem($search = null) {
		if ($this->primaryItemOverride) {
			return $this->primaryItemOverride;
		} elseif ($this->primaryItem) {
			return $this->primaryItem;
		} else {
			if ($search) {
				$item = $this->items()->where('transmoggable', '=', 1)->search($search)->orderBy('bnet_id', 'ASC')->first();
			}
			return ($search && $item) ? $item : $this->items()->where('transmoggable', '=', 1)->orderBy('bnet_id', 'ASC')->first();
		}
	}
	
	public function setTempPrimaryItem(Item $item) {
		$this->primaryItemOverride = $item;
	}
	
	public function downloadRenderFile() {
		$items = $this->items()->where('transmoggable', '=', 1)->get();
		
		foreach ($items as $item) {
			$url = Config::get('settings.bnet_item_renders_base_url') . 'item' . $item->bnet_id . '.jpg';
			$path = Config::get('settings.download_file_dir') . '/display-renders/' . $this->mogslot->url_token;
			$file = FileUpload::saveRemoteFile($url, $path, 'render_' . $this->id . '.jpg', true);
			
			if ($file) {
				$this->render_image_id = $file->id;
				$this->save();
				return $file;
			}
		}
		return false;
	}
	
	public static function getAllowedClassBitmaskForDisplays($displays) {
		$bitmask = false;
		
		foreach ($displays as $display) {
			if ($display->restricted_classes === null) {
				return null;
			}
			
			$bitmask = ($bitmask) ? $bitmask | $display->restricted_classes : $display->restricted_classes;
		}
		
		return $bitmask;
	}
	
	public static function getAllowedRaceBitmaskForDisplays($displays) {
		$bitmask = false;
		
		foreach ($displays as $display) {
			if ($display->restricted_races === null) {
				return null;
			}
			
			$bitmask = ($bitmask) ? $bitmask | $display->restricted_races : $display->restricted_races;
		}
		
		return $bitmask;
	}
}
