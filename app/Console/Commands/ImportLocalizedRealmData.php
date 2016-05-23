<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\BnetWowApi;
use App\Realm;
use \Config;

class ImportLocalizedRealmData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'realms:import-localized-data';

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
	    $apiClient = new BnetWowApi(Config::get('settings.bnet_api_key'), Config::get('settings.bnet_api_locale')); 
	    
        $realms = Realm::whereNull('localized_name')->get();
        $enDataByRegion = [];
        $slugsByRegion = [];
        $slugsByRegionLocale = [];
        
        foreach ($realms as $realm) {
	        if (!array_key_exists($realm->region, $enDataByRegion)) {
		        $enDataByRegion[$realm->region] = $apiClient->getRealmData($realm->region);
		        $slugsByRegion[$realm->region] = [];
		        
		        foreach ($enDataByRegion[$realm->region]['realms'] as $realmArr) {
			        foreach ($realmArr['connected_realms'] as $connectedSlug) {
				        $slugsByRegion[$realm->region][] = $connectedSlug;
			        }
		        }
	        }
	        
	        $data = $apiClient->getRealmData($realm->region, $realm->locale, $realm->name);
	        
	        if ($data && $data['realms']) {
		        if (true) {
			        $realm->localized_name = $data['realms'][0]['name'];
			        $realm->localized_url_token = $data['realms'][0]['slug'];
			        $realm->save();
		        } else {
			        $_key = $realm->region . '|' . $realm->locale;
			        if (!array_key_exists($_key, $slugsByRegionLocale)) {
				        $slugsByRegionLocale[$_key] = [];
				        
				        foreach ($data['realms'] as $realmArr) {
					        foreach ($realmArr['connected_realms'] as $connectedSlug) {
						        $slugsByRegionLocale[$_key][] = $connectedSlug;
					        }
				        }
			        }
			        
			        $slugPos = array_search($realm->url_token, $slugsByRegion[$realm->region]);
			        
			        if ($slugPos !== false) {
				        $realm->localized_name = 'CHECK - ' . $slugsByRegionLocale[$_key][$slugPos];
			        }
		        }
	        } else {
		        $this->line('Realm data not found for ' . $realm->name . ' - ' . $realm->region);
	        }
        }        
    }
}
