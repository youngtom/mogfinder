<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\ItemSource;
use App\Zone;

class ImportQuestZoneData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:sources:quests:import-zones';

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
        $sourceData = file(storage_path() . '/app/imports/questzonedata.txt');
        $questZones = [];
        
        foreach ($sourceData as $str) {
	        preg_match_all('/\[(?P<questid>[0-9:]+)(")?\] \= (?P<zoneid>\d+)/', $str, $matches);
	        
	        if (count($matches)) {
				$questID = @$matches['questid'][0];
				$zoneBnetID = @$matches['zoneid'][0];
				
				if ($questID && $zoneBnetID) {
					$zone = Zone::where('bnet_id', '=', $zoneBnetID)->first();
					
					if ($zone) {
						$questZones[$questID] = $zone->id;
					} else {
						$this->error('Zone not found: ' . $zoneBnetID . ' Quest: ' . $questID);
					}
				}
			}
        }
        
        $sources = ItemSource::where('item_source_type_id', '=', 7)->whereNull('zone_id')->whereIn('bnet_source_id', array_unique(array_keys($questZones)))->get();
        
        $bar = $this->output->createProgressBar($sources->count());
        
        foreach ($sources as $source) {
	        if (array_key_exists($source->bnet_source_id, $questZones)) {
		        $source->zone_id = $questZones[$source->bnet_source_id];
		        $source->save();
	        }
	        $bar->advance();
        }
        $bar->finish();
    }
}
