<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;
use DB;

class UpdateItemFactionInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:update-faction';

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
	    
        $items = Item::where('imported_from_bnet', '=', 0)->get();
	    $bar = $this->output->createProgressBar($items->count());
	    
	    $apiClient = new \App\BnetWowApi(\Config::get('settings.bnet_api_key'), \Config::get('settings.bnet_api_locale'));
	    
	    //$items->chunk(200, function ($items) use ($bar, $apiClient) {
		    $items->each(function($item) use ($bar, $apiClient) {
				$data = $item->getBnetData();
				
				if ($data) {
					$item->imported_from_bnet = 1;
				    $item->bnet_faction_id = (@$data['minFactionId']) ?: null;
				    $item->reputation_level = (@$data['minReputation']) ?: null;
				    $item->save();
				} else {
					$this->error('Unable to retrieve bnet data for item: ' . $item->id);
				}
				
				$bar->advance();
		    });
		//});
	    
	    $bar->finish();
    }
}
