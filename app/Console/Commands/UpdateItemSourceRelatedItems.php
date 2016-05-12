<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\ItemSource;

class UpdateItemSourceRelatedItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:sources:update-related-items';

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
        $sources = ItemSource::whereNotNull('item_currency_info')->orWhere('item_source_type_id', '=', 12)->orWhere('item_source_type_id', '=', 16)->get();
		$bar = $this->output->createProgressBar($sources->count());
		
		foreach ($sources as $source) {
			$source->updateSourceItem();
			$bar->advance();
		}
		
		$bar->finish();
    }
}
