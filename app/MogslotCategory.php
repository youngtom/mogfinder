<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MogslotCategory extends Model
{
	protected $visible = ['id', 'label', 'group', 'classmask'];
	protected $appends = ['classmask'];
	
    public function mogslots() {
		return $this->hasMany('App\Mogslot')->orderBy('inventory_type_id', 'ASC');
	}
	
	public function getClassmaskAttribute() {
		$mask = false;
		foreach ($this->mogslots as $mogslot) {
			if ($mogslot->allowed_class_bitmask === null) {
				return null;
			}
			$mask = ($mask === false ) ? $mogslot->allowed_class_bitmask : $mask | $mogslot->allowed_class_bitmask;
		}
		return $mask;
	}
}
