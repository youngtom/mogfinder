<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;
use App\ItemSource;
use DB;

class RemoveDuplicateItemSources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:sources:remove-duplicates {action}';

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
        DB::disableQueryLog();
	    ini_set('memory_limit','1024M');
	    ini_set('max_exeuction_time',0);
	    
	    $results = DB::table('item_sources')->groupBy('bnet_source_id', 'item_id', 'item_source_type_id')->havingRaw('count(*) > 1')->get();
	    
	    foreach ($results as $res) {
			$sources = ItemSource::where('bnet_source_id', '=', $res->bnet_source_id)->where('item_id', '=', $res->item_id)->where('item_source_type_id', '=', $res->item_source_type_id)->get();
			
			if ($this->argument('action') == 'list') {
				$this->info('itemID: ' . $res->item_id . ' - bnetSourceID: ' . $res->bnet_source_id . ' - type: ' . $res->item_source_type_id);
			}
			
			if ($this->argument('action') == 'delete') {
				$count = 0;
				foreach ($sources as $source) {
					if ($count > 0) {
						$source->delete();
					}
					$count++;
				}
			}
	    }
    }
}
