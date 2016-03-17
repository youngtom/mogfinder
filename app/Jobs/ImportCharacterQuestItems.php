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
    protected $user_datafile_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($characterID, $userDatafileID)
    {
        $this->character_id = $characterID;
        $this->user_datafile_id = $userDatafileID;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
	    $character = Character::findOrFail($this->character_id);
	    $dataFile = UserDatafile::findOrFail($this->user_datafile_id);
	    
        $character->importQuestItemData($dataFile);
    }
}
