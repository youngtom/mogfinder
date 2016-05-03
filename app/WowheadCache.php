<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WowheadCache extends Model
{
    private static $client = null;
    
    public static function getItemHtml($itemID) {
	    return self::_getHtml('item=' . $itemID);
    }
    
    private static function _getHtml($endpoint) {
	    if ($cache = self::where('endpoint', '=', $endpoint)->where('expiration', '>', time())->orderBy('expiration', 'DESC')->first()) {
		    return $cache->data;
	    } else {
			if (self::$client === null) {
				self::$client = new \GuzzleHttp\Client();
			}
			
		    $url = 'http://www.wowhead.com/' . $endpoint;
		    
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
