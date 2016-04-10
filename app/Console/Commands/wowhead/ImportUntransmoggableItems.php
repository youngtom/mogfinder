<?php

namespace App\Console\Commands\wowhead;

use Illuminate\Console\Command;
use App\Item;

class ImportUntransmoggableItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wowhead:items:import-untransmoggable {filename}';

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
        $filename = $this->argument('filename');
        if (!$filename || !file_exists($filename)) {
	        $this->error('Please specify a valid file');
	        return;
        }
        
        $json = file_get_contents($filename);
        
        $itemsArr = json_decode($json, true);
        
        if (!$itemsArr) {
	        $this->error('Malformed json');
	        return;
        }
        
        $bar = $this->output->createProgressBar(count($itemsArr));
        
        foreach ($itemsArr as $itemArr) {
	        $bnetID = $itemArr['id'];
	        
	        $items = Item::where('bnet_id', '=', $bnetID)->get();
	        
	        if (!$items->count()) {
		        $this->error('Item not found: ' . $bnetID);
	        } else {
		        foreach ($items as $item) {
			        if ($item->transmoggable) {
				        $this->line('Item ' . $bnetID . '(ItemID: ' . $item->id . ') - ' . $item->name . ' updated');
				        $item->transmoggable = 0;
				        $item->save();
			        }
		        }
	        }
	        
	        $bar->advance();
        }
        
        $bar->finish();
    }
}
