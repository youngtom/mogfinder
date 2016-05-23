@extends('layouts.app')

@section('css')
	<link href="{{ asset('css/items.css') }}" rel="stylesheet">
@stop

@section('js')
	<script src="{{ asset('js/items.js') }}"></script>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-10 col-md-offset-1 item-scroll-with-nav">
	        <div class="navbar subnav" role="navigation">
			    <div class="navbar-inner">
			        <div class="container">
				        <form class="navbar-form">
					        <div type="button" class="btn btn-primary quest-toggle-btn" data-collected="1">
					        	<i class="fa fa-btn fa-check-square-o"></i><i class="fa fa-btn fa-square-o"></i> &nbsp;Include Quest Items</span>
					        </div>
					        
					        <?php if ($characters && count($characters) > 1) { ?>
					        <div class="form-group character-filter-group">
						        <p class="navbar-text">Show duplicates only for character:</p>
						        <div class="btn-group navbar-btn <?=($selectedCharacter) ? '' : 'character-filter all-selected'?> selectable-filter" role="group">
									<button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										<?php if ($selectedCharacter) { ?>
										<i class="game-icon-sm" style="background-image: url(<?=($selectedCharacter->charClass) ? $selectedCharacter->charClass->getFile('icon_image')->getWebPath() : ''?>)"></i> <?=$selectedCharacter->name?> <span class="caret"></span>
										<?php } else { ?>
										<span class="selected-value">All</span> <span class="caret"></span>
										<?php } ?>
									</button>
									<ul class="dropdown-menu scrollable-menu">
										<li class="all-select"><a href="<?=url('wardrobe/duplicates')?>" data-class-id="0" class="show-all">Show all</a></li>
										<li class="all-select divider" roll="separator"></li>
									<?php 
											foreach ($characters as $realmID => $characterArr) {
									?>
										<li class="dropdown-header"><?=$characterArr[0]->realm->name?> (<?=$characterArr[0]->realm->region?>)</li>
									<?php
												foreach ($characterArr as $character) {
									?>
										<li>
											<a href="<?=url('wardrobe/duplicates/' . $character->url_token)?>" data-character-id="<?=$character->id?>">
												<i class="game-icon-sm" style="background-image: url(<?=($character->charClass) ? $character->charClass->getFile('icon_image')->getWebPath() : ''?>)"></i> <?=$character->name?><span class="realm-name"> - <?=$character->realm->name?> (<?=$character->realm->region?>)</span>
											</a>
										</li>
									<?php 
											}
										}
									?>
									</ul>
								</div>
					        </div>
					        <?php } ?>
				        </form>
					</div>
				</div>
			</div>
			
	        <?php 
		        foreach ($duplicates as $displayID => $items) {
			        $restrictedClasses = false;
			        foreach ($items as $item) {
				    	if ($item->item->allowable_classes || $item->item->allowable_races) {
					    	$restrictedClasses = true;
					    	break;
				    	}
				    }
				    
				    $items = $items->sortBy(function ($item) {
					    return $item->character->name;
				    });
		    ?>
            <div class="panel panel-default item-display-panel">
                <div class="panel-heading">Appearance <?=$displayID?>:</div>
                <table class="table table-hover item-list-table">
                    <thead>
                        <tr>
                            <th class="charname">Character</th>
                            <th class="charrealm">Realm</th>
                            <th class="itemloc">Location</th>
                            <th class="itemname">Item</th>
                            <th class="class-restrictions center"><?=($restrictedClasses) ? 'Restricted to' : '&nbsp;'?></th>
                            <th class="center bound">Bound?</th>
                        </tr>
                    </thead>
                    <tbody>
					<?php foreach ($items as $item) { ?>
						<tr class="item-row" data-character-id="<?=($item->character && $item->itemLocation->shorthand != 'quest') ? $item->character->id : 0?>" data-quest-item="<?=($item->itemLocation->shorthand == 'quest') ? 1 : 0?>">
							<?php if ($item->character) { ?>
							<td class="charname"><?=$item->character->name?></td>
							<td class="charrealm"><?=$item->character->realm->name?></td>
							<?php
								} elseif ($item->location_label) {	
									list($guildName, $guildRealm) = explode(' - ', $item->location_label);
							?>
							<td class="charname"><?=$guildName?></td>
							<td class="charrealm"><?=$guildRealm?></td>
							<?php } ?>
							<td class="itemloc"><?=ucwords($item->itemLocation->shorthand)?></td>
							<td class="dupeitemname" <?=(!$restrictedClasses) ? 'colspan="2"' : ''?>><a href="http://www.wowhead.com/item=<?=$item->item->bnet_id?>" target="_blank" rel="<?=$item->getWowheadMarkup()?>" class="item-link q<?=$item->getItemQuality()?>">[<?=$item->getName()?>]</a></td>
							<?php if ($restrictedClasses) { ?>
							<td class="class-restrictions center">
								<?php
									if ($item->item->allowable_classes || $item->item->allowable_races) {
										if ($item->item->allowable_classes) {
											foreach ($item->item->getRestrictedClasses() as $class) {
										
								?>
								<i class="game-icon-sm" style="background-image: url(<?=$class->getFile('icon_image')->getWebPath()?>)" title="<?=ucwords($class->name)?>" data-toggle="tooltip" data-placement="left"></i>
								<?php
											}
										}
										
										if ($item->item->allowable_races) {
											foreach ($item->item->getRestrictedFactions() as $faction) {
										
								?>
								<i class="game-icon-sm" style="background-image: url(<?=$faction->getFile('icon_image')->getWebPath()?>)" title="<?=ucwords($faction->name)?>" data-toggle="tooltip" data-placement="left"></i>
								<?php
											}
										}
									} else {
										echo '&nbsp;';
									}
								?>
							</td>
							<?php } ?>
							<td class="center bound">
							<?php
								switch ($item->bound) {
									case 1:
										echo '<i class="fa fa-btn fa-circle" title="Soulbound" data-toggle="tooltip" data-placement="left"></i>';
										break;
									case 2:
										echo '<i class="fa fa-btn fa-circle-o" title="Account Bound" data-toggle="tooltip" data-placement="left"></i>';
										break;
									/*
									case 0;
										echo '<i class="fa fa-btn fa-circle-thin" title="Not Bound" data-toggle="tooltip" data-placement="left"></i>';
										break;
									*/
									default:
										echo '&nbsp;';
										break;
								}
							?> 
							</td>
						</tr>
					<?php } ?>
                    </tbody>
                </table>
            </div>
            <?php
	            }
	        ?>
        </div>
    </div>
</div>
@endsection
