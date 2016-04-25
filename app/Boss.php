<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Boss extends Model
{
	protected $fillable = ['bnet_id', 'parent_boss_id'];
	
	public function parentBoss() {
		return $this->belongsTo('App\Boss', 'parent_boss_id');
	}
	
	public function encounter() {
		if ($this->parent_boss_id) {
			return $this->parentBoss;
		} else {
			return $this;
		}
	}
}
