<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Character;

class ImportCharacterQuests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:characters:import-quests {characterID?}';

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
        $charID = $this->argument('characterID');
        
        $characters = ($charID) ? Character::where('id', '=', $charID)->get() : Character::where('level', '>=', 10)->get();
        
        $bar = $this->output->createProgressBar($characters->count());
        
        foreach ($characters as $character) {
	        $this->info('Importing quests for ' . $character->name . ' - ' . $character->realm->name . ' (' . $character->realm->region . ')');
	        $questIDs = explode(',', $character->quests_imported);
	        
	        $newItems = 0;
	        if ($questIDs) {
		        $newItems = $character->importQuests($questIDs, true);
	        }
	        
	        $newItems += $character->importBnetQuestItemData();
	        $this->line($newItems . ' new item(s) added');
	        $bar->advance();
        }
        $bar->finish();
    }
}
