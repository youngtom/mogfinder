<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\FileUpload;
use Config;
use Sofa\Eloquence\Eloquence;

class ItemDisplay extends Model
{
	use Eloquence;
	use \App\Http\Traits\FileHandler;
	
	protected $primaryItemOverride = null;
	protected $searchableColumns = ['items.name'];
	protected $allowedClassBitmask = -1;
	protected $allowedRaceBitmask = -1;
	
	public function items() {
		return $this->hasMany('App\Item');
	}
	
	public function transmoggableItems() {
		return $this->hasMany('App\Item')->where('transmoggable', '=', 1)->orderBy('bnet_id', 'ASC');
	}
	
	public function primaryItem() {
		return $this->hasOne('App\Item', 'item_display_id', 'primary_item_id');
	}
	
	public function mogslot() {
		return $this->belongsTo('App\Mogslot');
	}
	
	public function getPrimaryItem($search = null) {
		if ($this->primaryItemOverride) {
			return $this->primaryItemOverride;
		} elseif ($this->primaryItem) {
			return $this->primaryItem;
		} else {
			if ($search) {
				$item = $this->items()->where('transmoggable', '=', 1)->search($search)->orderBy('bnet_id', 'ASC')->first();
			}
			return ($search && $item) ? $item : $this->items()->where('transmoggable', '=', 1)->orderBy('bnet_id', 'ASC')->first();
		}
	}
	
	public function setTempPrimaryItem(Item $item) {
		$this->primaryItemOverride = $item;
	}
	
	public function downloadRenderFile() {
		$items = $this->items()->where('transmoggable', '=', 1)->get();
		
		foreach ($items as $item) {
			$url = Config::get('settings.bnet_item_renders_base_url') . 'item' . $item->bnet_id . '.jpg';
			$path = Config::get('settings.download_file_dir') . '/display-renders/' . $this->mogslot->url_token;
			$file = FileUpload::saveRemoteFile($url, $path, 'render_' . $this->id . '.jpg', true);
			
			if ($file) {
				$this->render_image_id = $file->id;
				$this->save();
				return $file;
			}
		}
		return false;
	}
	
	public function getAllowedClassBitmask() {
		if ($this->allowedClassBitmask !== -1) {
			return $this->allowedClassBitmask;
		}
		
		$bitmasks = array_unique($this->items()->where('transmoggable', '=', 1)->lists('allowable_classes')->toArray());
		
		$bitmask = false;
		foreach ($bitmasks as $_bitmask) {
			if ($_bitmask == null) {
				$bitmask = null;
				break;
			}
			
			$bitmask = ($bitmask) ? $bitmask | $_bitmask : $_bitmask;
		}
		
		if (!$bitmask && $this->mogslot) {
			$bitmask = $this->mogslot->allowed_class_bitmask;
		}
		
		return $this->allowedClassBitmask = $bitmask;
	}
	
	public static function getAllowedClassBitmaskForDisplays($displays) {
		$bitmask = false;
		
		foreach ($displays as $display) {
			$dispBitmask = $display->getAllowedClassBitmask();
			
			if ($dispBitmask === null) {
				return null;
			}
			
			$bitmask = ($bitmask) ? $bitmask | $dispBitmask : $dispBitmask;
		}
		
		return $bitmask;
	}
	
	public function getAllowedRaceBitmask() {
		if ($this->allowedRaceBitmask !== -1) {
			return $this->allowedRaceBitmask;
		}
		
		$bitmasks = array_unique($this->items()->where('transmoggable', '=', 1)->lists('allowable_races')->toArray());
		
		$bitmask = false;
		foreach ($bitmasks as $_bitmask) {
			if ($_bitmask == null) {
				$bitmask = null;
				break;
			}
			
			$bitmask = ($bitmask) ? $bitmask | $_bitmask : $_bitmask;
		}
		
		if (!$bitmask) {
			$bitmask = null;
		}
		
		return $this->allowedRaceBitmask = $bitmask;
	}
	
	public static function getAllowedRaceBitmaskForDisplays($displays) {
		$bitmask = false;
		
		foreach ($displays as $display) {
			$dispBitmask = $display->getAllowedRaceBitmask();
			
			if ($dispBitmask === null) {
				return null;
			}
			
			$bitmask = ($bitmask) ? $bitmask | $dispBitmask : $dispBitmask;
		}
		
		return $bitmask;
	}
}
