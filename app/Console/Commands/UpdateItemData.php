<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Item;
use App\FileUpload;
use DB;

class UpdateItemData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:update-data';

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
	    
        $items = Item::where('imported_from_bnet', '=', 0)->orderBy('transmoggable', 'DESC')->get();
        
	    $bar = $this->output->createProgressBar($items->count());
	    $iconPath = 'app/downloads/icons/';
	    
	    $apiClient = new \App\BnetWowApi(\Config::get('settings.bnet_api_key'), \Config::get('settings.bnet_api_locale'));
	    
	    $bonusStats = [
		    3 => ['agi'],
		    4 => ['str'],
		    5 => ['int'],
		    71 => ['int', 'str', 'agi'],
		    72 => ['str', 'agi'],
		    73 => ['int', 'agi'],
		    74 => ['int', 'str']
	    ];
	    
	    //$items->chunk(200, function ($items) use ($bar, $apiClient, $iconPath) {
		    $items->each(function($item) use ($bar, $apiClient, $iconPath, $bonusStats) {
				$data = $item->getBnetData();
				
				if ($data) {
					if ($item->icon_image_id == null && @$data['icon']) {
						$filename = str_replace(' ', '_', $data['icon']) . '.png';
						$file = FileUpload::where('path', '=', $iconPath)->where('filename', '=', $filename)->first();
						
						if ($file) {
							$item->icon_image_id = $file->id;
						} else {
							$this->info('No icon found: ' . $filename);
						}
					}
					
					if ($item->primary_stats == null && @$data['bonusStats'] && count($data['bonusStats'])) {
						$stats = [];
						foreach ($data['bonusStats'] as $statArr) {
							$statID = $statArr['stat'];
							if (array_key_exists($statID, $bonusStats)) {
								$stats = array_merge($stats, $bonusStats[$statID]);
							}
						}
						
						if (count($stats)) {
							$item->primary_stats = implode(',', array_unique($stats));
						}
					}
					
					if ($item->bnet_itemset_id == null && @$data['itemSet']['id']) {
						$item->bnet_itemset_id = $data['itemSet']['id'];
					}
					
					$item->imported_from_bnet = 1;	
					$item->save();
				} else {
					$this->error('Unable to retrieve bnet data for item: ' . $item->id);
				}
				$bar->advance();
		    });
		//});
	    
	    $bar->finish();
    }
}
