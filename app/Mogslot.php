<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Mogslot extends Model
{	
	use \App\Http\Traits\FileHandler;
	
	protected $visible = ['id', 'simple_label', 'allowed_class_bitmask'];
	
	public function itemDisplays() {
		return $this->hasMany('App\ItemDisplay');
	}
}
