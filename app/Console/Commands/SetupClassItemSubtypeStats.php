<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\CharClass;
use App\ItemSubtype;
use App\ClassItemSubtypeStat;

class SetupClassItemSubtypeStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'classes:setup-subtype-stats';

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
        $subtypes = ItemSubtype::where('usable_by_class_bitmask', '>', 0)->get();
        $classes = CharClass::all();
        
        foreach ($subtypes as $subtype) {
	        foreach ($classes as $class) {
		        $classMask = pow(2, $class->id);
		        
		        if ($classMask & $subtype->usable_by_class_bitmask === 0) {
			        $subtypeStat = ClassItemSubtypeStat::where('class_id', '=', $class->id)->where('item_subtype_id', '=', $subtype->id)->first();
			        
			        if (!$subtypeStat) {
				        $subtypeStat = new ClassItemSubtypeStat;
				        $subtypeStat->class_id = $class->id;
				        $subtypeStat->item_subtype_id = $subtype->id;
			        }
		        }
	        }
        }
    }
}
