<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;
use DB;

class UpdateBitmasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:update-bitmasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates item race & class bitmasks';

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
        $items = DB::table('items')->whereNotNull('allowable_classes')->orWhereNotNull('allowable_races')->get();
        
        foreach ($items as $item) {
	        if ($item->allowable_classes !== null) {
		        $mask = Item::getBitmaskFromIDArray(explode(',', $item->allowable_classes));
		        $this->line('Item (classes): ' . $item->id . ' - ' . $item->allowable_classes . ' => ' . $mask);
		        $item->allowable_classes = $mask;
		    }
		    
		    if ($item->allowable_races !== null) {
		        $mask = Item::getBitmaskFromIDArray(explode(',', $item->allowable_races));
		        $this->line('Item (races): ' . $item->id . ' - ' . $item->allowable_races . ' => ' . $mask);
		        $item->allowable_races = $mask;
		    }
	        
	        DB::table('items')->where('id', $item->id)->update(['allowable_races' => $item->allowable_races, 'allowable_classes' => $item->allowable_classes]);
        }
    }
}
