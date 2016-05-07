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
					        <div class="form-group">
						        <p class="navbar-text">Filter: </p>
						        <div class="btn-group navbar-btn" role="group">
							        <div type="button" class="btn btn-primary collected-toggle-btn" data-collected="1">
							        	<i class="fa fa-btn fa-check-square-o"></i><i class="fa fa-btn fa-square-o"></i> &nbsp;Collected&nbsp; <span class="badge collected-count"><?=count($userDisplayIDs)?></span>
							        </div>
							        <div type="button" class="btn btn-primary collected-toggle-btn" data-collected="0">
							        	<i class="fa fa-btn fa-check-square-o"></i><i class="fa fa-btn fa-square-o"></i> &nbsp;Not Collected&nbsp; <span class="badge uncollected-count"><?=count($itemDisplays) - count($userDisplayIDs)?></span>
							        </div>
						        </div>
					        </div>
					        
					        <?php if ($classes && count($classes) > 1) { ?>
					        <div class="form-group class-filter-group">
						        <p class="navbar-text">Class:</p>
						        <div class="btn-group navbar-btn class-filter selectable-filter all-selected" role="group">
									<button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										<span class="selected-value">All</span> <span class="caret"></span>
									</button>
									<ul class="dropdown-menu">
										<li class="all-select"><a href="#" data-class-id="0" class="show-all">Show all</a></li>
										<li class="all-select divider" roll="separator"></li>
									<?php foreach ($classes as $class) { ?>
										<li><a href="#class:<?=$class->url_token?>" data-class-id="<?=$class->id?>" data-class-code="<?=$class->url_token?>" class="<?=$class->url_token?>">
											<i class="game-icon-sm" style="background-image: url(<?=$class->getFile('icon_image')->getWebPath()?>)"></i> <?=$class->name?></a>
										</li>
									<?php } ?>
									</ul>
								</div>
					        </div>
					        <?php } ?>
					        
					        <?php if ($factions && count($factions) > 1) { ?>
					        <div class="form-group faction-filter-group">
						        <p class="navbar-text">Faction:</p>
						        <div class="btn-group navbar-btn faction-filter selectable-filter all-selected" role="group">
									<button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										<span class="selected-value">All</span> <span class="caret"></span>
									</button>
									<ul class="dropdown-menu">
										<li class="all-select"><a href="#" data-faction-mask="0" class="show-all">Show all</a></li>
										<li class="all-select divider" roll="separator"></li>
									<?php foreach ($factions as $faction) { ?>
										<li><a href="#faction:<?=strtolower($faction->name)?>" data-faction-mask="<?=$faction->race_bitmask?>" data-faction-code="<?=strtolower($faction->name)?>" class="<?=strtolower($faction->name)?>">
											<i class="game-icon-sm" style="background-image: url(<?=$faction->getFile('icon_image')->getWebPath()?>)"></i> <?=$faction->name?></a>
										</li>
									<?php } ?>
									</ul>
								</div>
					        </div>
					        <?php } ?>
					        
					        <?php if ($itemSourceTypes && count($itemSourceTypes) > 1) { ?>
					        <div class="form-group source-filter-group">
						        <p class="navbar-text">Source:</p>
						        <div class="btn-group navbar-btn source-filter selectable-filter all-selected" role="group">
									<button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										<span class="selected-value">All</span> <span class="caret"></span>
									</button>
									<ul class="dropdown-menu">
										<li class="all-select"><a href="#" data-source-id="0" class="show-all">Show all</a></li>
										<li class="all-select divider" roll="separator"></li>
									<?php foreach ($itemSourceTypes as $itemSourceType) { ?>
										<li><a href="#source:<?=$itemSourceType->url_token?>" data-source-id="<?=$itemSourceType->id?>" data-source-code="<?=$itemSourceType->url_token?>"><?=$itemSourceType->simple_label?></a></li>
									<?php } ?>
									</ul>
								</div>
					        </div>
					        <?php } ?>
				        </form>
					</div>
				</div>
			</div>
			
	        <div class="panel-group item-display-group" id="display-accordion" role="tablist" aria-multiselectable="true">
	        <?php
		        foreach ($itemDisplays as $display) {
					$collected = in_array($display->id, $userDisplayIDs);
					$displayItems = $display->items()->where('transmoggable', '=', 1)->get();
					
					if ($collected) {
						$restrictedClassMask = 0;
						foreach ($displayItems as $item) {
							if (in_array($item->id, $userItemIDs)) {
								if (!$item->allowable_classes) {
									$restrictedClassMask = false;
									break;
								}
								
								$restrictedClassMask = $restrictedClassMask | $item->allowable_classes;
							}
						}
						
						if ($restrictedClassMask) {
							$restrictedClasses = $classes->filter(function ($class) use ($restrictedClassMask) {
								$classMask = pow(2, $class->id);
								return (($restrictedClassMask & $classMask) !== 0);
							})->lists('name')->toArray();
						}
					} else {
						$restrictedClassMask = false;
					}
		    ?>
	            <div class="collected-togglable panel panel-default item-display-panel" data-display-collected="<?=($collected) ? 1 : 0?>" data-display-collected-partial="<?=($restrictedClassMask && count($restrictedClasses)) ? 1 : 0?>">
	                <div class="panel-heading collapsed" id="display-heading-<?=$display->id?>" role="button" data-toggle="collapse" data-parent="#display-accordion" href="#display-<?=$display->id?>" aria-expanded="false" aria-controls="display-<?=$display->id?>">
		                <i class="fa fa-btn fa-plus expand-icon" title="expand"></i>
		                <i class="fa fa-btn fa-minus collapse-icon" title="collapse"></i> Display <?=$display->id?>
		                <span class="display-item-info">
			                -
			                <span class="display-item-link"><a href="http://www.wowhead.com/item=<?=$display->getPrimaryItem()->bnet_id?>" target="_blank" rel="<?=$display->getPrimaryItem()->getWowheadMarkup()?>" class="item-link q<?=$display->getPrimaryItem()->quality?>">[<?=$display->getPrimaryItem()->name?>]</a></span>
			                <?php if (count($displayItems) > 1) { ?>
			                <small class="num-addl-items">(and <?=count($displayItems) - 1?> other<?=(count($displayItems) > 2) ? 's' : ''?>)</small>
			                <?php } ?>
		                </span>
		                
		                <span class="pull-right">
		                	<?php if ($restrictedClassMask && count($restrictedClasses)) { ?>
		                	<i class="fa fa-btn fa-check partiallly-collected-star" title="Unlocked on:<br><?=implode('<br>', $restrictedClasses)?>" data-toggle="tooltip" data-placement="left"></i>
		                	<?php } ?>
		                	<i class="fa fa-btn fa-check collected-star" title="Collected" data-toggle="tooltip" data-placement="left"></i>
		                </span>
		            </div>		            
		            <div id="display-<?=$display->id?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="display-heading-<?=$display->id?>">
			            <div class="panel-body row">
				            <div class="col-md-3 render-col">
					            <?php if ($render = $display->getFile('render_image')) { ?>
					            <a href="<?=$display->getURL('wardrobe')?>" class="item-render-wrapper">
						            <div class="item-display-render" style="background-image: url(<?=$render->getWebPath(280, 280, 'crop', 0, 0)?>)"></div>
						            <img src="<?=asset('img/blank.png')?>" />
					            </a>
					            <?php } ?>
				            </div>
				            <div class="col-md-9 item-list-col">
					            <table class="table table-hover item-list-table pull-right">
				                    <thead>
				                        <tr>			                            
				                            <th>Item</th>
											<th>Source</th>
				                            <th class="center">Collected?</th>
				                        </tr>
				                    </thead>
				                    <tbody>
									<?php
										foreach ($displayItems as $item) {
											$sources = $sourceTypeIDs = [];
											foreach ($item->itemSources as $itemSource) {
												if ($itemSource->itemSourceType->url_token) {
													$sourceText = ($itemSource->getWowheadLink($item) && $itemSource->itemSourceType->context_label) ? '<a href="' . $itemSource->getWowheadLink($item) . '" target="_blank">' . $itemSource->getSourceText() . '</a>' : $itemSource->itemSourceType->simple_label;
													
													$sources[] = $sourceText;
													$sourceTypeIDs[] = $itemSource->itemSourceType->id;
												}
											}
											$sources = implode(', ', $sources);
											$sourceTypeIDs = implode('|', $sourceTypeIDs);
											$priority = (is_array($priorityItemIDs) && in_array($item->id, $priorityItemIDs)) ? 'priority' : '';
											
											if ($mogslot || $item->allowable_classes) {
												$itemClassMask = ($item->allowable_classes) ?: 0;
											}  elseif ($display->mogslot) {
												$itemClassMask = ($display->mogslot->allowed_class_bitmask) ?: 0;
											} else {
												$itemClassMask = 0;
											}
									?>
										<tr class="item-row <?=$priority?>" data-classmask="<?=$itemClassMask?>" data-racemask="<?=($item->allowable_races) ?: 0?>" data-sources="<?=($sourceTypeIDs) ?: -1?>" data-item-collected="<?=(in_array($item->id, $userItemIDs)) ? 1 : 0?>">
											<td class="itemname"><a href="http://www.wowhead.com/item=<?=$item->bnet_id?>" target="_blank" rel="<?=$item->getWowheadMarkup()?>" class="item-link q<?=$item->quality?>">[<?=$item->name?>]</a></td>
											<td class="source"><?=$sources?></td>
											<td class="center collected">
											<?php 
												if (in_array($item->id, $userItemIDs)) {
													if ($item->allowable_classes || $item->allowable_races) {
														echo '<i class="fa fa-btn fa-check partiallly-collected-star"></i>';
													} else {
														echo '<i class="fa fa-btn fa-check"></i>';
													}
												} else {
													echo '&nbsp;';
												}	
											?>
											</td>
										</tr>
									<?php 
										}
									?>
				                    </tbody>
				                </table>
				            </div>
			            </div>
		            </div>
	            </div>
            <?php
	            }
	        ?>
	        </div>
        </div>
    </div>
</div>
@endsection
