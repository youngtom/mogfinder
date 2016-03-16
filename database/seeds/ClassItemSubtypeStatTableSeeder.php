<?php

use Illuminate\Database\Seeder;
use App\CharClass;
use App\ItemSubtype;
use App\ClassItemSubtypeStat;

class ClassItemSubtypeStatTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $subtypes = ItemSubtype::where('usable_by_classes_bitmask', '>', 0)->get();
        $classes = CharClass::all();
        
        foreach ($subtypes as $subtype) {
	        foreach ($classes as $class) {
		        $classMask = pow(2, $class->id);
		        
		        if (($classMask & $subtype->usable_by_classes_bitmask) != 0) {
			        $subtypeStat = ClassItemSubtypeStat::where('class_id', '=', $class->id)->where('item_subtype_id', '=', $subtype->id)->first();
			        
			        if (!$subtypeStat) {
				        $subtypeStat = new ClassItemSubtypeStat;
				        $subtypeStat->class_id = $class->id;
				        $subtypeStat->item_subtype_id = $subtype->id;
						$subtypeStat->save();
			        }
		        }
	        }
        }
    }
}
