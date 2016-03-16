<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MogslotCategory extends Model
{
    public function mogslots() {
		return $this->hasMany('App\Mogslot')->orderBy('inventory_type_id', 'ASC');
	}
}
