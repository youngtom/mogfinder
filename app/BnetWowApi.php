<?php

namespace App;

use Config;
use App\Realm;

class BnetWowApi
{
	private $defaultParameters = array();
	private static $client = null;
	
	public function __construct($apiKey, $locale = 'en_US') {
		$this->defaultParameters = array(
			'apikey' => $apiKey,
			'locale' => $locale
		);
	}
	
	public function getItemData($itemID, $context = '', $bonus = '') {
		$endpoint = '/item/' . $itemID;
		$params = array();
		
		if ($context) {
			$endpoint .= '/' . $context;
		}
		
		if ($bonus) {
			$params['bl'] = $bonus;
		}
		return $this->_getEndpointData($endpoint, 'us', $params);
	}
	
	public function getCharacterData($name, $realm, $region, $fields = []) {
		$endpoint = '/character/' . $realm . '/' . $name;
		$params = array();
		
		if ($fields) {
			$params['fields'] = implode(',', $fields);
		}
		return $this->_getEndpointData($endpoint, $region, $params, 60*10);
	}
	
	public function getAuctionData(Realm $realm) {
		$endpoint = '/auction/data/' . $realm->name;
		
		return $this->_getEndpointData($endpoint, $realm->region, [], 360);
	}
	
	public function getZoneData() {
		return $this->_getEndpointData('/zone/', 'us');
	}
	
	public function getRealmData($region, $locale = 'en_US', $realm = false) {
		$params = ['locale' => $locale];
		
		if ($realm) {
			$params['realm'] = $realm;
		}
		
		return $this->_getEndpointData('/realm/status', $region, $params);
	}
	
    private function _getEndpointData($endpoint, $region = 'us', $params = array(), $expirationOverride = false) {
	    $baseURLByRegion = [
		    'us' => 'https://us.api.battle.net/wow',
		    'eu' => 'https://eu.api.battle.net/wow',
		    'kr' => 'https://kr.api.battle.net/wow',
		    'tw' => 'https://tw.api.battle.net/wow',
		    'cn' => 'https://api.battlenet.com.cn/wow'
	    ];
	    
	    $region = strtolower($region);
	    $baseURL = (array_key_exists($region, $baseURLByRegion)) ? $baseURLByRegion[$region] : Config::get('settings.bnet_api_base_url_default');
	    
	    $searchURL = $baseURL . $endpoint;
	    $searchURL .= (count($params)) ? '?' . http_build_query($params) : '';
	    $params = array_merge($this->defaultParameters, $params);
	    $url = $baseURL . $endpoint . '?' . http_build_query($params);
	    
		if ($cache = BnetApiCache::where('request_uri', '=', $searchURL)->where('expiration', '>', time())->orderBy('expiration', 'DESC')->first()) {
			return json_decode($cache->data, true);
		} else {
			$res = self::makeRequest($url);
			
			if ($res) {
				$cache = new BnetApiCache;
				$cache->request_uri = $searchURL;
				$cache->endpoint = $endpoint;
				$expireTime = ($expirationOverride) ?: Config::get('settings.bnet_api_cache_expiration');
				$cache->expiration = time() + $expireTime;
				$cache->data = $res;
				$cache->save();
				
				return json_decode($res, true);
			} else {
				$cache = BnetApiCache::where('request_uri', '=', $url)->orderBy('expiration', 'DESC')->first();
				
				return ($cache) ? $cache->data : false;
			}
		}
	}
	
	public static function makeRequest($url) {
		if (self::$client === null) {
			self::$client = new \GuzzleHttp\Client();
		}
		
		try {
			$res = self::$client->request('GET', $url, ['http_errors' => false]);
		} catch (\Exception $e) {			
			return false;
		}
		
		if ($res) {
			switch ($res->getStatusCode()) {
				case 200:
					return $res->getBody();
				case 404:
					return null;
				default:
					return false;
			}
		} else {
			return false;
		}
	}
}
