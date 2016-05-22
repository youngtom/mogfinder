<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WowheadCache extends Model
{
	protected $table = 'wowhead_cache';
    private static $client = null;
    
    public static function getItemHtml($itemID) {
	    return self::_getHtml('item=' . $itemID);
    }
    
    public static function getLegacyItemHtml($itemID) {
	    return self::_getHtml('item=' . $itemID, 'http://wotlk.openwow.com/');
    }
    
    private static function _getHtml($endpoint, $baseURL = 'http://www.wowhead.com/') {
	    $url = $baseURL . $endpoint;
	    
	    if ($cache = self::where('request_uri', '=', $url)->where('expiration', '>', time())->orderBy('expiration', 'DESC')->first()) {
		    return $cache->data;
	    } else {
			if (self::$client === null) {
				self::$client = new \GuzzleHttp\Client();
			}
		    
		    try {
				$res = self::$client->request('GET', $url);
			} catch (\Exception $e) {
				return false;
			}
			
			if ($res) {
				$cache = new static;
				$cache->request_uri = $url;
				$cache->endpoint = $endpoint;
				$expireTime = 60*60*24*30; // 30 days
				$cache->expiration = time() + $expireTime;
				$cache->data = (string)$res->getBody();
				$cache->save();
				
				return $cache->data;
			} else {
				return false;
			}   
	    }
    }
}
