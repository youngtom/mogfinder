<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Character;

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
	
	public function getResponseDataArray() {
		$data = [
			'current' => $this->progress_current,
			'total' => $this->progress_total,
			'new' => $this->progress_new
		];
		
		if ($this->response) {
			$data['data'] = json_decode($this->response, true);
		}
		
		return $data;
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
		
		if (isset($this->import_data['appearances']) && is_array($this->import_data['appearances'])) {
			$count = count($this->import_data['appearances']);
		}
		
		return $count;
	}
}
