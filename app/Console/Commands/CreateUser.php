<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use Hash;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create {username} {password}';

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
        $username = $this->argument('username');
        $password = $this->argument('password');
        
        if ($username && $password) {
	        $user  = new User;
	        $user->name = $username;
	        $user->password = Hash::make($password);
	        $user->save();
        }
    }
}
