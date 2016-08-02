<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use App\Item;

class UpdateItemBonuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:update-bonuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import battle.net item bonuses';

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
	    $items = Item::where('bonus', '<>', NULL)->where('bnet_id', '<', 133585)->get();
	    $bar = $this->output->createProgressBar(count($items));
	    
	    foreach ($items as $item) {
		    $oldBonus = $item->bonus;
		    if ($newBonus = $item->updateBnetBonusData()) {
			    if ($newBonus != $oldBonus) {
				    $this->line('Item (' . $item->bnet_id . ') bonus changed from ' . $oldBonus . ' to ' . $newBonus);
				}
		    }
		    
		    $bar->advance();
	    }
	    
	    $bar->finish();
    }
}
