<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Item;

class ItemSource extends Model
{
    public function item() {
        return $this->belongsTo('App\Item');
	}
	
	public function itemSourceType() {
        return $this->belongsTo('App\ItemSourceType');
	}
	
	public function zone() {
        return $this->belongsTo('App\Zone');
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
		if ($this->itemSourceType->label == 'CONTAINED_IN_ITEM' && $item = Item::where('bnet_id', '=', $this->bnet_source_id)->first()) {
			$replace = [
				'{$item_name}' => $item->name,
				'{$item_quality}' => $item->quality
			];
			
			return strtr($this->itemSourceType->context_label, $replace);	
		} else {
			return $this->itemSourceType->context_label;
		}
	}
}
