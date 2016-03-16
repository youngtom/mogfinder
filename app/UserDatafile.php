<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;

class UserDatafile extends Model
{
	public $dataArr = null;
    protected $responseData = null;
    
    public static function boot() {
		parent::boot();
		
		self::saving(function ($datafile) {
			$datafile->response = ($datafile->responseData) ? json_encode($datafile->responseData) : null;
		});
		
		self::updating(function ($datafile) {
			$datafile->response = ($datafile->responseData) ? json_encode($datafile->responseData) : null;
		});
	}
	
	public function setResponseData($field, $value) {
		if ($this->responseData === null) {
			$this->responseData = json_decode($this->response, true);
		}
		return $this->responseData[$field] = $value;
	}
	
	public function incrementResponseData($field, $value) {
		if ($this->responseData === null) {
			$this->responseData = json_decode($this->response, true);
		}
		return $this->responseData[$field] = (@$this->responseData[$field]) ? $this->responseData[$field] + $value : $value;
	}
	
	public function getImportDataAttribute($value) {
		if ($this->dataArr === null && $value) {
			$this->dataArr = json_decode($value, true);
		}
		return $this->dataArr;
	}
	
	public function getItemCount() {
		$count = 0;
		
		if (@$this->import_data['heirlooms'] && is_array($this->import_data['heirlooms'])) {
			$count += count($this->import_data['heirlooms']);	
		}
		
		foreach ($this->import_data['chars'] as $charTag => $charData) {
		    $character = Auth::user()->getCharacterFromDataTag($charTag, false);
		    
		    if ($character) {
				$scanTime = (@$charData['scanTimes']) ? max($charData['scanTimes']['inventory'], $charData['scanTimes']['bank'], $charData['scanTimes']['bags']) : 0;
				
				if ($scanTime > $character->last_scanned) {
					if (@$charData['equipped']) {
						$count += count($charData['equipped']);
					}
					
					if (@$charData['items']) {
						foreach ($charData['items'] as $itemArr) {
							$count += count($itemArr);
						}
					}
				}
		    }
		}
		
		return $count;
	}
}
