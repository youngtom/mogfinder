<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\UserDatafile;
use App\Character;
use App\UserItem;

class ResetUserData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:reset-data {userid}';

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
        $user = User::find($this->argument('userid'));
        
        if (!$user) {
	        $this->error("Invalid user specified.");
	        return;
        }
        
        Character::where('user_id', '=', $user->id)->delete();
        UserItem::where('user_id', '=', $user->id)->delete();
        UserDatafile::where('user_id', '=', $user->id)->delete();
        UserItemDisplay::where('user_id', '=', $user->id)->delete();
    }
}
