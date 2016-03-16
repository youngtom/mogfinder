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
    
    protected $user;
    protected $userDatafile;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, UserDatafile $userDatafile)
    {
        $this->user = $user;
        $this->userDatafile = $userDatafile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->user->importUserData($this->userDatafile);
    }
}
