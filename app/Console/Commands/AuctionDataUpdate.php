<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Realm;
use DB;

class AuctionDataUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auctions:update';

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
	    
        $timestamp = time() - 30*60;
        $realms = Realm::whereNull('parent_realm_id')->where('auction_data_timestamp', '<=', $timestamp)->orWhere('auction_data_timestamp', '=', null)->get();
        
        foreach ($realms as $realm) {
	        $this->line('Updating auction data for ' . $realm->name . ' ' . $realm->region);
	        $realm->updateAuctionData();
        }
    }
}
