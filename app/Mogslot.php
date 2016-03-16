<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Mogslot extends Model
{	
	use \App\Http\Traits\FileHandler;
	
	public function itemDisplays() {
		return $this->hasMany('App\ItemDisplay');
	}
}
