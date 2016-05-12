<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Item;
use App\Zone;

class ItemSource extends Model
{
	public static function boot() {
		parent::boot();
		
		self::saved(function ($source) {
			if ($source->item && $source->item->itemDisplay && $source->isDirty('zone_id')) {
				$source->item->itemDisplay->updateZones();
			}
			
			if ($source->zone && $source->isDirty('item_id')) {
				$source->zone->updateDisplays();
			}
		});
		
		self::saving(function ($source) {
			if ($source->isDirty('item_currency_info')) {
				$source->updateSourceItem(false);
			}
		});
		
		self::deleted(function ($source) {
			$item = Item::find($source->item_id);
			
			if ($item) {
				$display = ItemDisplay::find($item->item_display_id);
				
				if ($display) {
					$display->updateZones();
				}
			}
			
			$zone = Zone::find($source->zone_id);
			
			if ($zone) {
				$zone->updateDisplays();
			}
		});
	}
	
    public function item() {
        return $this->belongsTo('App\Item');
	}
	
	public function itemSourceType() {
        return $this->belongsTo('App\ItemSourceType');
	}
	
	public function zone() {
        return $this->belongsTo('App\Zone');
	}
	
	public function updateSourceItem($save = true) {
		if (!$this->itemSourceType) {
			return false;
		}
		
		if ($this->itemSourceType->label == 'CREATED_BY_ITEM' || $this->itemSourceType->label == 'CONTAINED_IN_ITEM') {
			$sourceItem = Item::where('bnet_id', '=', $this->bnet_source_id)->first();
			
			if ($sourceItem) {
				$this->source_item_id = $sourceItem->id;
			}
		} elseif ($this->itemSourceType->label == 'VENDOR' && $this->item_currency_info) {
			$currArr = json_decode($this->item_currency_info, true);
			
			foreach ($currArr as $itemArr) {
				list($bnetID, $amt) = $itemArr;
				
				if ($amt == 1) {
					$item = Item::where('bnet_id', '=', $bnetID)->first();
					
					if ($item->isItemToken()) {
						$this->source_item_id = $item->id;
						break;
					}
				}
			}
		} else {
			$this->source_item_id = null;
		}
				
		if ($save) {
			$this->save();
		}
	}
	
	public function getWowheadLink(Item $item) {
		if (!$this->itemSourceType->wowhead_link_format) {
			return false;
		}
		
		$replace = [
			'{$bnet_id}' => $this->bnet_source_id,
			'{$item_faction_id}' => $item->bnet_faction_id
		];
		
		return 'http://www.wowhead.com/' . strtr($this->itemSourceType->wowhead_link_format, $replace);
	}
	
	public function getSourceText() {
		if (($this->itemSourceType->label == 'CONTAINED_IN_ITEM' || $this->itemSourceType->label == 'CREATED_BY_ITEM') && $item = Item::where('bnet_id', '=', $this->bnet_source_id)->first()) {
			$replace = [
				'{$item_name}' => $item->name,
				'{$item_quality}' => $item->quality
			];
			
			return strtr($this->itemSourceType->context_label, $replace);	
		} else {
			return $this->itemSourceType->context_label;
		}
	}
	
	public static function getCurrencyBnetIDs() {
		$sources = ItemSource::whereNotNull('item_currency_info')->get(['item_currency_info'])->groupBy('item_currency_info');
		
		$bnetIDs = [];
		foreach ($sources as $currencyInfo => $sourceArr) {
			$currencyArr = json_decode($currencyInfo, true);
			foreach ($currencyArr as $arr) {
				list($bnetID, $amt) = $arr;
				$bnetIDs[] = $bnetID;
			}
		}
		
		return array_unique($bnetIDs);
	}
}
