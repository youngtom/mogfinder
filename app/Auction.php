<?php

namespace App;

use App\Item;
use Illuminate\Database\Eloquent\Model;

class Auction extends Model
{
	protected $fillable = ['bnet_id', 'realm_id', 'item_id', 'seller', 'price', 'bonuses'];
	static protected $validItemIDs = null;
	
	public function item() {
		return $this->belongsTo('App\Item');
	}
	
	public function realm() {
		return $this->belongsTo('App\Realm');
	}
    
    public static function getValidItemIDs() {
	    if (self::$validItemIDs === null) {
		    self::$validItemIDs = Item::where('transmoggable', '=', 1)->groupBy('bnet_id')->get()->lists('bnet_id')->toArray();
	    }
	    return self::$validItemIDs;
    }
    
    public function getSignature() {
	    return md5(implode('||', [$this->seller, $this->item_id, $this->bonuses, $this->realm, $this->bid, $this->buyout]));
    }
    
    public static function formatPrice($price) {
	    $g = floor($price / 10000);
	    $r = $price % 10000;
	    $s = floor($r / 100);
	    $c = $r % 100;
	    return number_format($g) . 'g ' . $s . 's ' . $c . 'c';
    }
    
    public static function processAuctionData($realm, $data) {
	    $validIDs = self::getValidItemIDs();
	    $out = [];
	    self::where('realm_id', '=', $realm->id)->update(['updated' => 0]);
	    
	    foreach ($data as $auctionArr) {
		    if (in_array($auctionArr['item'], $validIDs)) {
			    $auction = self::where('bnet_id', '=', $auctionArr['auc'])->where('realm_id', '=', $realm->id)->first();
			    
			    if (!$auction) {
				    $auction = new Auction;
				    $auction->bnet_id = $auctionArr['auc'];
				    $auction->realm_id = $realm->id;
				    $auction->seller = $auctionArr['owner'];
				    $auction->buyout = (@$auctionArr['buyout']) ?: null;
				    
				    $bonuses = [];
					if (@$auctionArr['bonusLists'] && count($auctionArr['bonusLists'])) {
						foreach ($auctionArr['bonusLists'] as $bonus) {
							$bonuses[] = $bonus['bonusListId'];
						}
						
						$item = Item::where('transmoggable', '=', 1)->where('bnet_id', '=', $auctionArr['item'])->whereIn('bonus', $bonuses)->orderBy('bonus', 'ASC')->first();
					} else {
						$item = false;
					}
				    
					if (!$item) {
						$item = Item::where('transmoggable', '=', 1)->where('bnet_id', '=', $auctionArr['item'])->orderBy('bonus', 'ASC')->first();
					}
						
					if ($item) {
						$auction->item_id = $item->id;
						$auction->item_display_id = $item->item_display_id;
						$auction->bonuses = (count($bonuses)) ? implode(',', $bonuses) : null;
					} else {
						$auction = false;
					}
				}
				
				if ($auction) {
					$auction->bid = $auctionArr['bid'];
					$auction->timeleft = $auctionArr['timeLeft'];
					$auction->updated = 1;
					$auction->save();
				}
			}
	    }
	    
	    self::where('realm_id', '=', $realm->id)->where('updated', '=', 0)->delete();
    }
}
