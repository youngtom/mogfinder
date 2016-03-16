<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;
use DB;

class ImportWowdbSources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'item:import-wowdb';

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
	    die('needs updating to match new source storage');
	    DB::disableQueryLog();
	    ini_set('memory_limit','1024M');
	    
	    $client = new \GuzzleHttp\Client();
        $items = Item::where('item_display_id', '>', 0)->where('transmoggable', '=', 1)->where('imported_from_wowdb', '=', 0)->get();
        
        $items->each(function ($item) use ($client) {
	        $url = 'http://www.wowdb.com/api/item/' . $item->bnet_id;
	        $res = $client->request('GET', $url);
	        
	        if ($res && $res->getStatusCode() == 200) {
		        $out = substr($res->getBody(), 1, -1);
		        $data = json_decode($out, true);
		        //$this->line('Item: ' . $item->name . ' (' . $item->bnet_id . ') - Source: ' . @$data['Source'] . ' SingleSourceType: ' . @$data['SingleSourceType'] . ' SingleSourceID: ' . @$data['SingleSourceID']);
		        
		        $item->wowdb_source = @$data['Source'];
		        $item->wowdb_source_type = @$data['SingleSourceType'];
		        $item->wowdb_source_id = @$data['SingleSourceID'];
		        $item->imported_from_wowdb = 1;
		        $item->save();
	        } else {
		        $this->error('Item: ' . $item->name . ' (' . $item->bnet_id . ')');
	        }
        });
    }
}
