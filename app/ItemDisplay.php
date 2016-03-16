<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\FileUpload;
use Config;

class ItemDisplay extends Model
{
	use \App\Http\Traits\FileHandler;
	
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
	
	public function getPrimaryItem() {
		if ($this->primaryItem) {
			return $this->primaryItem;
		} else {
			return $this->items()->where('transmoggable', '=', 1)->orderBy('bnet_id', 'ASC')->first();
		}
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
}
