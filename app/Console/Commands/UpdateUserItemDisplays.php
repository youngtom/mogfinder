<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use App\UserItem;

class UpdateUserItemDisplays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update-item-displays';

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
        DB::disableQueryLog();
	    ini_set('memory_limit','1024M');
	    ini_set('max_exeuction_time',0);
	    
        $userItems = UserItem::all();
	    $bar = $this->output->createProgressBar(count($userItems));
	    
	    foreach ($userItems as $userItem) {
			$userItem->touch();
			$userItem->save();
			
			$bar->advance();
	    }
	    
	    $bar->finish();
    }
}
