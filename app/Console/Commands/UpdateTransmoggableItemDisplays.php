<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\ItemDisplay;
use DB;

class UpdateTransmoggableItemDisplays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:display:update-xmog';

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
	    
	    $itemDisplays = ItemDisplay::all();
	    $bar = $this->output->createProgressBar(count($itemDisplays));
	    
	    $itemDisplays->each(function($itemDisplay) use ($bar) {
			$xmoggable = (count($itemDisplay->items()->where('transmoggable', '=', 1)->get())) ? 1 : 0;
			
			if ($xmoggable != $itemDisplay->transmoggable) {
				$this->line("\n" . 'Updating: ' . $itemDisplay->id . ' (Current: ' . $itemDisplay->transmoggable . ' New: ' . $xmoggable . ')');
				$itemDisplay->transmoggable = $xmoggable;
				$itemDisplay->mogslot_id = ($xmoggable) ? $itemDisplay->mogslot_id : null;
				$itemDisplay->save();
			}
			
			$bar->advance();
	    });
	    
	    $bar->finish();
    }
}
