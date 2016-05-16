<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Config;
use App\BnetWowApi;
use App\Auction;

class Realm extends Model
{
	private static $apiClient = null;
    protected $fillable = ['name', 'region'];
    protected $connectedRealms = null;
    
    public function characters() {
	    return $this->hasMany('App\Character');
    }
    
    public function parentRealm() {
	    return $this->hasOne('App\Realm');
    }
    
    public function updateAuctionData() {
	    if ($this->parent_realm_id) {
		    return false;
	    }
	    
	    if (self::$apiClient === null) {
			self::$apiClient = new BnetWowApi(Config::get('settings.bnet_api_key'), Config::get('settings.bnet_api_locale'));  
	    }
	    
	    $checkRes = self::$apiClient->getAuctionData($this);
	    
	    if ($checkRes) {
			$dataLastModified = floor(@$checkRes['files'][0]['lastModified'] / 1000);
			$dataUrl = @$checkRes['files'][0]['url'];
			
			if ($dataLastModified > $this->auction_data_timestamp && $dataUrl) {
				$auctionJson = BnetWowApi::makeRequest($dataUrl);
				
				if ($auctionJson) {
					$dataArr = json_decode((string)$auctionJson, true);
					Auction::processAuctionData($this, $dataArr['auctions']);
					
					$this->auction_data_timestamp = $dataLastModified;
					$this->save();
					
					//update connected realm auction data
					if (count($dataArr['realms']) > 1) {
						foreach ($dataArr['realms'] as $realmNameArr) {
							$realmName = $realmNameArr['name'];
							
							if ($realmName != $this->name) {
								$realm = Realm::firstOrCreate([
									'name' => $realmName,
									'region' => $this->region
								]);
								
								$realm->parent_realm_id = $this->id;
								$realm->auction_data_timestamp = $dataLastModified;
								$realm->save();
							}
						}
					}
				}
			}
		}
    }
    
    public function getConnectedRealms() {
	    if ($this->connectedRealms === null) {
			if ($this->parent_realm_id) {
				$this->connectedRealms = Realm::where('id', '=', $this->id)->orWhere('id', '=', $this->parent_realm_id)->orWhere('parent_realm_id', '=', $this->parent_realm_id)->get();
		    } else {
			    $this->connectedRealms = Realm::where('id', '=', $this->id)->orWhere('parent_realm_id', '=', $this->id)->get();
		    }    
	    }
	    return $this->connectedRealms;
    }
    
    public static function importRealms($region) {
	    if (self::$apiClient === null) {
			self::$apiClient = new BnetWowApi(Config::get('settings.bnet_api_key'), Config::get('settings.bnet_api_locale'));  
	    }
	    
	    $data = self::$apiClient->getRealmData($region);
	    dd($data);
	    foreach ($data as $realmArr) {
		    dd($realmArr);
	    }
    }
}
