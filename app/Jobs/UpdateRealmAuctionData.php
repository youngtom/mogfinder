<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Realm;

class UpdateRealmAuctionData extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    protected $realm_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Realm $realm)
    {
        $this->realm_id = $realm->id;
        $realm->auction_import_queued = 1;
        $realm->save();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $realm = Realm::findOrFail($this->realm_id);
        
        $realm->updateAuctionData();
        $realm->auction_import_queued = 0;
        $realm->save();
    }
}
