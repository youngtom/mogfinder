<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Realm;
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
        $realms = Realm::whereNull('parent_realm_id')->where('auction_import_queued', '=', 0)->where('auction_data_timestamp', '<=', $timestamp)->orWhere('auction_data_timestamp', '=', null)->get();
        
        foreach ($realms as $realm) {
	        $this->line('Queuing auction data import for ' . $realm->name . ' ' . $realm->region);
	        
	        $job = (new UpdateRealmAuctionData($realm))->onQueue('low');
		    $this->dispatch($job);
        }
    }
}
