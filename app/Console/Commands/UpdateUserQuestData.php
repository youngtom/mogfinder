<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Jobs\ImportCharacterQuestItems;
use Illuminate\Foundation\Bus\DispatchesJobs;

class UpdateUserQuestData extends Command
{
	use DispatchesJobs;
	
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:update-quests {userid}';

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
	   	$userID = $this->argument('userid');
	   	
	   	if ($userID == 'all') {
		   	$users = User::all();
	   	} else {
		   	$users = User::where('id', '=', $userID)->get();
	   	}
        
        if (!$users) {
	        $this->error("Invalid user specified.");
	        return;
        }
        
        foreach ($users as $user) {
	        $this->info('User: ' . $user->email);
	        foreach ($user->characters as $character) {
		        $character->quest_import_token = null;
		        $character->quests_imported = null;
		        $character->save();
		        
		        $job = (new ImportCharacterQuestItems($character->id))->onQueue('low');
			    $this->dispatch($job);
			    $this->line('Character quests reset and queued: ' . $character->name . ' - ' . $character->realm->name);
	        }
        }
    }
}
