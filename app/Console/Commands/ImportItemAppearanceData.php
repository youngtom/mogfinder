<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Libraries\LuaParser;
use App\Item;

class ImportItemAppearanceData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:import-appearance-data {filename}';

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
        
        $parser = new LuaParser(file_get_contents($filename));
        
        $data = ($parser) ? $parser->parse() : false;
			
		if ($parser && $data && @$data['MCCISaved']) {
			foreach ($data['MCCISaved'] as $itemIDstr => $str) {
				$itemID = str_replace('item', '', $itemIDstr);
				list($appearanceID, $xmoggable) = explode('|', $str);
				
				$item = Item::find($itemID);
				
				if ($item) {
					$item->appearance_id = ($appearanceID) ?: $item->appearance_id;
					
					if ($item->transmoggable != $xmoggable) {
						if ($item->bnet_id > 133586 || $xmoggable == 0) {
							$item->transmoggable = $xmoggable;
						} elseif ($appearanceID) {
							\Log::info('Xmog mismatch - ' . $xmoggable . ': ' . $item->name . ' (' . $item->bnet_id . ')');
							$this->line('Xmog mismatch - ' . $xmoggable . ': ' . $item->name . ' (' . $item->bnet_id . ')');
						}
					}
					
					$item->save();
				}
			}
		}
    }
}
