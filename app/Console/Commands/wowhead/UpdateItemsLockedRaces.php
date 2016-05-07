<?php

namespace App\Console\Commands\wowhead;

use Illuminate\Console\Command;
use App\Item;
use App\Faction;

class UpdateItemsLockedRaces extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wowhead:items:update-locked-races';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $items = Item::where('transmoggable', '=', 1)->get();
        
        $bar = $this->output->createProgressBar(count($items));
        
        $factionMasks = Faction::where('race_bitmask', '>', 0)->get()->lists('id', 'race_bitmask')->toArray();
        dd($factionMasks);
	    
	    foreach ($items as $item) {
		    $html = WowheadCache::getItemHtml($item->bnet_id);
		    
		    preg_match_all('/This item will be converted to \<a href\="\/item\=(?P<itemid>\d+)" (.+)\>(.+)\<\/a\> if you transfer to \<span class\="icon\-(?P<faction>alliance|horde)"\>(Alliance|Horde)\<\/span\>\./', $html, $matches, PREG_SET_ORDER);
		    
		    if ($matches && $matches[0]['faction']) {
			    
		    }
		    
		    $bar->advance();
	    }
	    
	    $bar->finish();
    }
}
