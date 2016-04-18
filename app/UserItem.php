<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\UserItemDisplay;

class UserItem extends Model
{
	public static function boot() {
		parent::boot();
		
		self::saved(function ($userItem) {			
			$userDisplay = UserItemDisplay::where('item_display_id', '=', $userItem->item_display_id)->where('user_id', '=', $userItem->user_id)->first();
			
			if (!$userDisplay) {
				$userDisplay = new UserItemDisplay;
				$userDisplay->user_id = $userItem->user_id;
				$userDisplay->item_display_id = $userItem->item_display_id;
				$userDisplay->restricted_classes = ($userItem->item) ? $userItem->item->allowable_classes : null;
				$userDisplay->restricted_races = ($userItem->item) ? $userItem->item->allowable_races : null;
				$userDisplay->save();
			} elseif ($userItem->isDirty('item_display_id') || $userItem->isDirty('allowable_classes') || $userItem->isDirty('allowable_races')) {
				$userDisplay->updateRestrictions();
			}
		});
		
		self::deleted(function ($item) {
			$userDisplay = UserItemDisplay::where('item_display_id', '=', $this->item_display_id)->where('user_id', '=', $this->user_id);
			
			if ($display) {
				$userDisplay->updateRestrictions();
			}
		});
	}
	
    public function itemLocation() {
        return $this->belongsTo('App\ItemLocation');
	}
	
	public function user() {
		return $this->belongsTo('App\User');
	}
	
	public function item() {
		return $this->belongsTo('App\Item');
	}
	
	public function character() {
		return $this->belongsTo('App\Character');
	}
	
	public function getName() {
		if (!$this->item_link) {
			return $this->item->name;
		}
		
		$linkInfo = Item::parseItemLink($this->item_link);
		return $linkInfo['name'];
	}
	
	public function getWowheadMarkup() {
		if (!$this->item_link) {
			return $this->item->name;
		}
		
		$linkInfo = Item::parseItemLink($this->item_link);
		$removeBonuses = [653];
		return (count($linkInfo['bonuses'])) ? 'bonus=' . implode(':', array_diff($linkInfo['bonuses'], $removeBonuses)) : '';
	}
	
	public function getItemQuality() {
		if ($this->item_link) {
			$linkInfo = Item::parseItemLink($this->item_link);
			$upgradeBonuses = [
				'3' => [171], // rare upgrades
				'4' => [15, 545, 761, 762, 763, 651, 764, 765, 766, 648, 642, 754, 755, 756, 757, 758, 759, 760, 761]  // epic upgrades
			];
			
			foreach ($upgradeBonuses as $quality => $bonuses) {
				if (count(array_intersect($linkInfo['bonuses'], $bonuses))) {
					return $quality;
				}
			}
		}
		
		return $this->item->quality;
	}
}
