<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\UserItem;

class UserItemDisplay extends Model
{
    public function updateRestrictions($save = true) {
		$classmask = $racemask = 0;
		$items = UserItem::where('user_id', '=', $this->user_id)->where('item_display_id', '=', $this->item_display_id)->get();
		
		if (!$items->count()) {
			$this->delete();
			return;
		}
		
		foreach ($items as $userItem) {
			$classmask = ($userItem->item->allowable_classes && $classmask !== null) ? $classmask | $userItem->item->allowable_classes : null;
			$racemask = ($userItem->item->getAllowedRaceMask() && $racemask !== null) ? $racemask | $userItem->item->getAllowedRaceMask() : null;
			
			if ($classmask === null && $racemask === null) {
				break;
			}
		}
		
		$this->restricted_classes = $classmask ?: null;
		$this->restricted_races = $racemask ?: null;
		
		if ($save) {
			$this->save();
		}
	}
}
