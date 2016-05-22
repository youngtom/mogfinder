<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Realm;
use App\Character;
use DB;
use App\Jobs\UpdateRealmAuctionData;
use Illuminate\Foundation\Bus\DispatchesJobs;

class AuctionDataUpdate extends Command
{
	use DispatchesJobs;
	
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
        $timestamp = time() - 30*60;
        $realms = Realm::whereNull('parent_realm_id')->where('auction_data_timestamp', '<=', $timestamp)->orWhere('auction_data_timestamp', '=', null)->get();
        
        foreach ($realms as $realm) {
	        $realmIDs = Realm::where('parent_realm_id', '=', $realm->id)->orWhere('id', '=', $realm->id)->get()->lists('id')->toArray();
	        $characters = Character::whereIn('realm_id', $realmIDs)->get();
	        
	        if ($characters->count()) {
		        \Log::info('Updating auction data for realm: ' . $realm->name . ' - ' . $realm->region);
		        $this->line('Updating auction data for realm: ' . $realm->name . ' - ' . $realm->region);
		        $realm->updateAuctionData();
		    }
        }
    }
}
