<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Item;

class CharClass extends Model
{
	use \App\Http\Traits\FileHandler;
	
	public $table = 'classes';
	protected $visible = ['id', 'name'];
	
    public function characters() {
        return $this->hasMany('App\Character');
	}
	
	public function eligibleForQuestReward(Item $item) {
		$classSubtypeStat = ClassItemSubtypeStat::where('class_id', '=', $this->id)->where('item_subtype_id', '=', $item->item_subtype_id)->first();
		
		if (!$classSubtypeStat || $classSubtypeStat->stats === null) {
			return true;
		} elseif ($classSubtypeStat->stats == 'none') {
			return false;
		} else {
			$validStats = explode(',', $classSubtypeStat->stats);
			$itemStats = explode(',', $item->primary_stats);			
			
			return !empty(array_intersect($validStats, $itemStats));
		}
	}
}
