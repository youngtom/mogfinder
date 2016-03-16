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
    
    protected $character;
    protected $quests;
    protected $userDatafile;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Character $character, $quests, UserDatafile $userDatafile)
    {
        $this->character = $character;
        $this->quests = $quests;
        $this->userDatafile = $userDatafile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->character->importQuestItemData($this->quests, $this->userDatafile);
    }
}
