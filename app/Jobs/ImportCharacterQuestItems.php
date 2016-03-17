<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Character;
use App\UserDatafile;

class ImportCharacterQuestItems extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    
    protected $character_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($characterID)
    {
        $this->character_id = $characterID;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
	    $character = Character::findOrFail($this->character_id);
	    
        $character->importQuestItemData();
    }
}
