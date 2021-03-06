<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\User;
use App\UserDatafile;

class ImportUserData extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    
    protected $user_id;
    protected $user_datafile_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userID, $userDatafileID)
    {
        $this->user_id = $userID;
        $this->user_datafile_id = $userDatafileID;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
	    $user = User::findOrFail($this->user_id);
	    
        $user->importUserData($this->user_datafile_id);
    }
}
