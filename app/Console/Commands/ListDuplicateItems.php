<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;

class ListDuplicateItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:items:list-duplicates {userid} {includeQuestItems}';

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
        
        $includeQuestItems = (bool) $this->argument('includeQuestItems');
        
        $dupeItems = $user->getDuplicateItems($includeQuestItems);
        
        foreach ($dupeItems as $displayID => $itemArr) {
	        $this->info('DisplayID: ' . $displayID);
	        foreach ($itemArr as $item) {
		        if ($item->character) {
			        $this->line('- ' . $item->item->name . ' - ' . $item->character->name . ' - ' . $item->character->realm->name . ' (' . ucwords($item->itemLocation->shorthand) . ')');
			    } else {
				    $this->line('- ' . $item->item->name . ' (' . ucwords($item->itemLocation->shorthand) . ')');
			    }
	        }
        }
    }
}
