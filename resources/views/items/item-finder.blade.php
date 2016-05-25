@extends('layouts.app')

@section('css')
	<link href="{{ asset('css/items.css') }}" rel="stylesheet">
	<link href="{{ asset('css/appearance-search.css') }}" rel="stylesheet">
@stop

@section('js')
	<script src="{{ asset('js/class-type-toggle.js') }}"></script>
	<script src="{{ asset('js/item-finder.js') }}"></script>
	<script src="{{ asset('js/items.js') }}"></script>
@stop

@section('content')
@include('shared.class-type-toggle', ['classes' => $allClasses])

<script>
	var bossesByZone = <?=$bossesByZone->toJson()?>;
</script>

<div class="container-fluid">
	<div class="row">
		<div class="col-md-7 col-md-offset-3">
			<form class="form-horizontal" id="appearance-finder-form" method="get">
				<div class="form-group">
					<div class="col-sm-2">
						<label for="item_name" class="control-label">Item Name:</label>
					</div>
					<div class="col-sm-6">
						<input type="text" class="form-control" id="item_name" name="item_name" value="{{ old('item_name') }}" placeholder="">
					</div>
				</div>
				<div class="form-group">
					<div class="col-sm-2">
				        <label class="control-label" for="class">Usable By:</label>
					</div>
			        <div class="col-sm-3">
				        <select class="form-control" name="faction" id="faction">
					        <option value="" data-keep="1">Faction</option>
					        <?php foreach ($allFactions as $faction) { ?>
								<option value="<?=$faction->id?>" <?=(old('faction') == $faction->id) ? 'selected="selected"' : ''?>><?=$faction->name?></option>
					        <?php } ?>
				        </select>
			        </div>
			        <div class="col-sm-3">
				        <select class="form-control" name="class" id="class">
					        <option value="" data-keep="1">Class</option>
					        <?php foreach ($allClasses as $class) { ?>
								<option value="<?=$class->id?>" data-classmask="<?=pow(2, $class->id)?>" <?=(old('class') == $class->id) ? 'selected="selected"' : ''?>><?=$class->name?></option>
					        <?php } ?>
				        </select>
			        </div>
		        </div>
		        <div class="form-group">
			        <div class="col-sm-2">
			    		<label class="control-label" for="cat">Type:</label>
			        </div>
			    	<div class="col-sm-3">
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
				    </div>
				    <div class="col-sm-3">
					    <select class="form-control <?=($selectedCat) ? '' : 'hidden'?>" name="slot" id="slot">
					    <?php if ($selectedCat) { ?>
					    	<option value="" data-keep="1">All</option>
					    	<?php foreach ($selectedCat->mogslots as $slot) { ?>
					    		<option value="<?=$slot->id?>" data-classmask="<?=$slot->allowed_class_bitmask?>" <?=(old('slot') == $slot->id) ? 'selected="selected"' : ''?>><?=$slot->simple_label?></option>
					    	<?php } ?>
					    <?php } ?>
				        </select>
				    </div>
		        </div>
		        <div class="form-group">
					<div class="col-sm-2">
				        <label class="control-label" for="class">Source:</label>
					</div>
			        <div class="col-sm-6">
				        <select class="form-control" name="source" id="source">
					        <option value="" data-keep="1">Any</option>
					        <?php foreach ($allSources as $sourceType) { ?>
								<option value="<?=$sourceType->url_token?>" <?=(old('source') == $sourceType->url_token) ? 'selected="selected"' : ''?>><?=$sourceType->simple_label?></option>
					        <?php } ?>
				        </select>
			        </div>
			        <?php /*
			        <div class="col-sm-3">
				    	<div id="only-this-source" class="checkbox <?=(old('source')) ? '' : 'hidden'?>">
							<label>
								<input type="checkbox" name="only_selected_source" value="1" id="only_selected_source" <?=(old('source') && old('only_selected_source')) ? 'checked="checked"' : ''?>> Only this source
							</label>
						</div>
			        </div>
			        */?>
		        </div>
		        <div class="form-group">
			        <div class="col-sm-2">
			    		<label class="control-label" for="zone">Zone:</label>
			        </div>
			    	<div class="col-sm-3">
				        <select class="form-control" name="zone" id="zone">
					        <option value="" data-keep="1">Any</option>
					        <?php foreach ($zoneCategories as $category) { ?>
						        <optgroup label="<?=($category->group == 'dungeons' || $category->group == 'raids') ? ucwords($category->group) . ' - ' : ''?><?=$category->name?>">
							        <?php foreach ($zonesByCategory[$category->id] as $zone) { ?>
							        <option value="<?=$zone->id?>" data-instanced="<?=($zone->is_dungeon || $zone->is_raid) ? 1 : 0?>" <?=($selectedZone && $selectedZone->id == $zone->id) ? 'selected="selected"' : ''?>><?=$zone->name?></option>
							        <?php } ?>
						        </optgroup>
					        <?php } ?>
					        </optgroup>
				        </select>
				    </div>
				    <div class="col-sm-3">
					    <select class="form-control <?=($selectedZone && $selectedZone->bosses->count()) ? '' : 'hidden'?>" name="boss" id="boss">
					    <?php if ($selectedZone && $selectedZone->bosses->count()) { ?>
					    	<option value="" data-keep="1">All Bosses</option>
					    	<?php foreach ($selectedZone->bosses as $boss) { ?>
					    		<option value="<?=$boss->id?>" <?=(old('boss') == $boss->id) ? 'selected="selected"' : ''?>><?=$boss->name?></option>
					    	<?php } ?>
					    <?php } ?>
				        </select>
				    </div>
		        </div>
		        <div class="form-group">
					<div class="col-sm-2">
				        <label class="control-label" for="collected">Include:</label>
					</div>
			        <div class="col-sm-2">
				    	<div class="checkbox">
							<label>
								<input type="checkbox" name="show_collected" value="1" selected="selected" <?=(old('show_collected')) ? 'checked="checked"' : ''?>> Collected
							</label>
						</div>
			        </div>
			        <div class="col-sm-2">
				    	<div class="checkbox">
							<label>
								<input type="checkbox" name="show_uncollected" value="1" <?=(old('show_uncollected') || !$submitted) ? 'checked="checked"' : ''?>> Not Collected
							</label>
						</div>
			        </div>
		        </div>
				<div class="form-group">
					<div class="col-sm-2"></div>
					<div class="col-sm-6">
						<button type="submit" class="btn btn-primary"><i class="fa fa-btn fa-search"></i> Find Appearances</button>
					</div>
				</div>
			</form>
	    </div>
	</div>
    <div class="row">
        <div class="col-md-10 col-md-offset-1 <?=(@count($itemDisplays) > 1) ? 'item-scroll-with-nav' : ''?>">
	        <?php if (false && @count($itemDisplays) > 1) { ?>
	        @include('items.shared.display-filter')
			<?php } ?>
			
	        <?php if (@$headerText) { ?>
	        <div class="display-title">
		        <h2><?=$headerText?></h2>
	        </div>
	        <?php } ?>
			
			<?php if (@$searchError || @$submitted && !@count($itemDisplays)) { ?>
			<div id="no-displays-alert" class="alert alert-info" role="alert">{{ $searchError or 'No appearances found matching that search. Please try again.' }}</div>
			<?php } ?>
			
	        <?php if (@$itemDisplays) { ?>
	        @include('items.shared.display-listing')
	        <?php } ?>
        </div>
    </div>
</div>
@endsection
