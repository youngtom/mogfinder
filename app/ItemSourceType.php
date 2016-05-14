<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Item;

class ItemSourceType extends Model
{
    public function getSourceDataHTML(Item $item, $sources, $maxListed = 1) {
	    $_sources = collect();
	    
	    if (!$this->url_token) {
		    return $_sources;
	    }
	    
	    foreach ($sources as $itemSource) {
			if ($sources->count() > $maxListed) { //combine sources together
				$wowheadLink = 'http://www.wowhead.com/' . $itemSource->getWowheadMarkup($item, $this->wowhead_link_format_multi);
				$label = ($this->plural_label) ?: $this->simple_label;
				
				$sourceText = ($wowheadLink) ? '<a href="' . $wowheadLink . '" class="no-tooltip" target="_blank">' . $label . '</a>' : $label;
				$_sources->push($sourceText);
				break;
			} else {
				$localLink = $itemSource->getLocalLink();
				$wowheadMarkup = $itemSource->getWowheadMarkup($item, $this->wowhead_link_format);
				$link = (!$localLink && $wowheadMarkup) ? 'http://www.wowhead.com/' . $wowheadMarkup : $localLink;
				
				$sourceText = ($link && $this->context_label) ? '<a href="' . $link . '" rel="' . $wowheadMarkup . '" target="_blank">' . $itemSource->getSourceText() . '</a>' : $this->simple_label;
				$_sources->push($sourceText);
			}
		}
		
		return $_sources->unique();
    }
}
