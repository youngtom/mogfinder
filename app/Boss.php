<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

class Boss extends Model
{
	use Eloquence;
	
	protected $fillable = ['bnet_id', 'parent_boss_id'];
	protected $searchableColumns = ['name'];
	protected $visible = ['id', 'name'];
	
	public function parentBoss() {
		return $this->belongsTo('App\Boss', 'parent_boss_id');
	}
	
	public function zone() {
		return $this->belongsTo('App\Zone');
	}
	
	public function encounter() {
		if ($this->parent_boss_id) {
			return $this->parentBoss;
		} else {
			return $this;
		}
	}
}
