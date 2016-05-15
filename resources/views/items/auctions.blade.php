@extends('layouts.app')

@section('css')
	<link href="{{ asset('css/items.css') }}" rel="stylesheet">
@stop

@section('js')
	<script src="{{ asset('js/auctions.js') }}"></script>
@stop

@section('content')
<script>
	var mogslots = <?=$mogslots->toJson()?>;
	var categories = <?=$mogslotCategories->toJson()?>;
	var classes = <?=$classes->toJson()?>;
</script>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-10 col-md-offset-1 item-scroll-with-nav">
	        <div class="navbar subnav" role="navigation">
			    <div class="navbar-inner">
			        <div class="container">
				        <div class="navbar-header">
					        <div class="navbar-brand">Auction Search</div>
				        </div>
				        <form class="navbar-form" action="{{ url('/wardrobe/auctions') }}" method="get">
					        <div class="form-group">
						        <label class="control-label" for="class">Class:</label>
						        <select class="form-control" name="class" id="class">
							        <option value="" data-keep="1">Any</option>
							        <?php foreach ($classes as $class) { ?>
										<option value="<?=$class->id?>" data-classmask="<?=pow(2, $class->id)?>" <?=($selectedClass && $selectedClass->id == $class->id) ? 'selected="selected"' : ''?>><?=$class->name?></option>
							        <?php } ?>
						        </select>
					        </div>
					        <div class="form-group">
						        <label class="control-label" for="cat">Type:</label>
						        <select class="form-control" name="cat" id="cat">
							        <option value="" data-keep="1">Select a Type</option>
							        <?php foreach ($mogslotCategories->groupBy('group') as $group => $categories) { ?>
								        <optgroup label="<?=ucwords($group)?>" data-group="<?=$group?>">
									        <?php foreach ($categories as $category) { ?>
									        <option value="<?=$category->id?>" data-classmask="<?=$category->classmask?>" <?=($selectedCat && $selectedCat->id == $category->id) ? 'selected="selected"' : ''?>><?=$category->label?></option>
									        <?php } ?>
								        </optgroup>
							        <?php } ?>
							        </optgroup>
						        </select>						        
						        <select class="form-control <?=($selectedCat) ? '' : 'hidden'?>" name="slot" id="slot">
							    <?php if ($selectedCat) { ?>
							    	<option value="" data-keep="1">All</option>
							    	<?php foreach ($selectedCat->mogslots as $slot) { ?>
							    		<option value="<?=$slot->id?>" data-classmask="<?=$slot->allowed_class_bitmask?>" <?=($selectedSlot && $selectedSlot->id == $slot->id) ? 'selected="selected"' : ''?>><?=$slot->simple_label?></option>
							    	<?php } ?>
							    <?php } ?>
						        </select>
					        </div>
							<button type="submit" class="btn btn-primary">Search</button>
				        </form>
					</div>
				</div>
			</div>
			
			<?php if (@$error) { ?>
			<div class="alert alert-danger" role="alert"><?=$error?></div>
			<?php } elseif ($auctions === false) { ?>
			<div class="alert alert-info" role="alert">Select an item type to search for auctions. You may also specify a specific class to narrow search results further.</div>
			<?php } else { ?>
			
	        <div class="auction-display-group">
	        <?php
		        foreach ($auctions as $displayID => $displayAuctions) {
			        $display = \App\ItemDisplay::find($displayID);
		    ?>
	            <div class="panel panel-default item-display-panel">
	                <div class="panel-heading"><a href="<?=$display->getURL('wardrobe')?>" class="display-link">Appearance <?=$displayID?></a> <?=(!$selectedSlot) ? '(' . $display->mogslot->singular_label . ')' : ''?></div>
	                <table class="table table-hover auction-list-table">
	                    <thead>
	                        <tr>
		                        <th class="item">Item</th>
	                            <th class="realm">Realm</th>
	                            <th class="seller">Seller</th>
	                            <th class="price">Bid</th>
	                            <th class="price">Buyout</th>
	                        </tr>
	                    </thead>
	                    <tbody>
						<?php foreach ($displayAuctions as $auction) { ?>
							<tr class="item-row auction-row">
								<td class="item"><a href="http://www.wowhead.com/item=<?=$auction->item->bnet_id?>" target="_blank" rel="<?=($auction->bonuses) ? 'bonus=' . str_replace(',', ':', $auction->bonuses) : ''?>" class="item-link q<?=$auction->item->quality?>">[<?=$auction->item->name?>]</a></td>
								<td class="realm"><?=$auction->realm->name?> (<?=$auction->realm->region?>)</td>
								<td class="seller"><?=$auction->seller?></td>
								<td class="price"><?=$auction->bid ? \App\Auction::formatPrice($auction->bid): '&nbsp;'?></td>
								<td class="price"><?=$auction->buyout ? \App\Auction::formatPrice($auction->buyout) : '&nbsp;'?></td>
							</tr>
						<?php } ?>
	                    </tbody>
	                </table>
	            </div>
            <?php
	            }
	        ?>
	        </div>
	        <?php } ?>
        </div>
    </div>
</div>
@endsection
