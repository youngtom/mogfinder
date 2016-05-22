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
	
	public function getResponseDataArray() {
		$data = [
			'current' => $this->progress_current,
			'total' => $this->progress_total
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
		
		if (isset($this->import_data['heirlooms']) && is_array($this->import_data['heirlooms'])) {
			$count += count($this->import_data['heirlooms']);	
		}
		
		if (!is_array($this->import_data['chars'])) {
			\Log::error('Missing char data in user_id: ' . $this->user_id . ' fileid: ' . $this->file_id);
			return 0;
		}
		
		foreach ($this->import_data['chars'] as $charTag => $charData) {
		    $character = Character::where('wow_guid', '=', $charTag)->where('user_id', '=', Auth::user()->id)->first();
		    
		    if ($character || count($charData['charInfo'])) {
			    $lastScanned = ($character) ? $character->last_scanned : 0;
			    
			    $scanTime = (@$charData['scanTime']) ? $charData['scanTime'] : 0;
				
				if ($scanTime > $lastScanned) {
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
		
		if (@$this->import_data['guilds'] && is_array($this->import_data['guilds']) && count($this->import_data['guilds'])) {
		    foreach ($this->import_data['guilds'] as $guildID => $guildData) {
			    if (@$guildData['guildInfo'] && @$guildData['guildInfo']['faction'] && @$guildData['guildInfo']['realm'] && @$guildData['guildInfo']['region'] && @$guildData['items']) {
				    $guildRealm = Realm::where('name', '=', $guildData['guildInfo']['realm'])->where('region', '=', ucwords($guildData['guildInfo']['region']))->first();
				    $guildFaction = Faction::where('name', '=', $guildData['guildInfo']['faction'])->first();
				    
				    if ($guildRealm && $guildFaction) {
					    foreach ($guildData['items'] as $tabID => $itemArr) {
						    $count += count($itemArr);
					    }
				    }
			    }
			}
		}
		
		return $count;
	}
}
