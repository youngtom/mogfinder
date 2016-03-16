<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Character;
use App\UserDatafile;

class ImportCharacterItems extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    
    protected $character;
    protected $charData;
    protected $userDatafile;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Character $character, $charData, UserDatafile $userDatafile)
    {
        $this->character = $character;
        $this->charData = $charData;
        $this->userDatafile = $userDatafile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->character->importItemData($this->charData, $this->userDatafile);
    }
}
