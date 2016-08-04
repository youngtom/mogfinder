<div class="panel-group item-display-group" id="display-accordion" role="tablist" aria-multiselectable="true">
<?php
    foreach ($itemDisplays as $display) {
		$collected = in_array($display->id, $userDisplayIDs);
		$displayItems = $display->items->where('transmoggable', 1);
		
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
			} else {
				$restrictedClasses = [];
			}
			
			$restrictedRaceMask = 0;
			foreach ($displayItems as $item) {
				if (in_array($item->id, $userItemIDs)) {
					if (!$item->getAllowedRaceMask()) {
						$restrictedRaceMask = false;
						break;
					}
					
					$restrictedRaceMask = $restrictedRaceMask | $item->getAllowedRaceMask();
				}
			}
			
			if ($restrictedRaceMask) {
				$restrictedFactions = $factions->filter(function ($faction) use ($restrictedRaceMask) {
					return (($restrictedRaceMask & $faction->race_bitmask) !== 0);
				})->lists('name')->toArray();
			} else {
				$restrictedFactions = [];
			}
			
			$restrictedUnits = [];
			if (count($restrictedClasses) && count($restrictedFactions)) {
				foreach ($restrictedFactions as $_faction) {
					foreach ($restrictedClasses as $_class) {
						$restrictedUnits[] = $_faction . ' ' . $_class;
					}
				}
			} elseif (count($restrictedClasses)) {
				$restrictedUnits = $restrictedClasses;
			} elseif (count($restrictedFactions)) {
				$restrictedUnits = $restrictedFactions;
			}
			
			$partiallyCollected = (count($restrictedUnits)) ? 1 : 0;
		} else {
			$partiallyCollected = $restrictedClassMask = false;
		}
		$displayPrimaryItem = $display->getPrimaryItem($priorityItemIDs);
?>
    <div class="collected-togglable panel panel-default item-display-panel" data-display-collected="<?=($collected) ? 1 : 0?>" data-display-collected-partial="<?=$partiallyCollected?>">
        <div class="panel-heading collapsed" id="display-heading-<?=$display->id?>" role="button" data-toggle="collapse" data-parent="#display-accordion" href="#display-<?=$display->id?>" aria-expanded="false" aria-controls="display-<?=$display->id?>">
            <i class="fa fa-btn fa-plus expand-icon" title="expand"></i>
            <i class="fa fa-btn fa-minus collapse-icon" title="collapse"></i> &nbsp; Appearance <?=$display->id?> <?=(!$mogslot) ? '(' . $display->mogslot->singular_label . ')' : ''?>
            <span class="display-item-info">
                -
                <?php if ($displayPrimaryItem) { ?>
                <span class="display-item-link"><a href="http://www.wowhead.com/item=<?=$displayPrimaryItem->bnet_id?>" target="_blank" rel="<?=$displayPrimaryItem->getWowheadMarkup()?>" class="item-link q<?=$displayPrimaryItem->quality?>">[<?=$displayPrimaryItem->name?>]</a></span>
				<?php } ?>
				
                <?php if (count($displayItems) > 1) { ?>
                <small class="num-addl-items">(and <?=count($displayItems) - 1?> other<?=(count($displayItems) > 2) ? 's' : ''?>)</small>
                <?php } ?>
            </span>
            
            <span class="pull-right">
            	<?php if ($partiallyCollected) { ?>
            	<i class="fa fa-btn fa-check partiallly-collected-star" title="Unlocked on:<br><?=implode('<br>', $restrictedUnits)?>" data-toggle="tooltip" data-placement="left"></i>
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
								$sourceTypeIDs = implode('|', $item->itemSources()->where('hidden', '=', 0)->get()->lists('item_source_type_id')->toArray());
								$priority = (is_array($priorityItemIDs) && in_array($item->id, $priorityItemIDs)) ? 'priority' : '';
								
								if ($mogslot || $item->allowable_classes) {
									$itemClassMask = ($item->allowable_classes) ?: 0;
								}  elseif ($display->mogslot) {
									$itemClassMask = ($display->mogslot->allowed_class_bitmask) ?: 0;
								} else {
									$itemClassMask = 0;
								}
						?>
							<tr class="item-row <?=$priority?>" data-classmask="<?=$itemClassMask?>" data-racemask="<?=($item->getAllowedRaceMask()) ?: 0?>" data-sources="<?=($sourceTypeIDs) ?: -1?>" data-item-collected="<?=(in_array($item->id, $userItemIDs)) ? 1 : 0?>">
								<td class="itemname"><a href="http://www.wowhead.com/item=<?=$item->bnet_id?>" target="_blank" rel="<?=$item->getWowheadMarkup()?>" class="item-link q<?=$item->quality?>">[<?=$item->name?>]</a></td>
								<td class="source"><?=$item->getSourceDataHTML()?></td>
								<td class="center collected">
								<?php 
									if (in_array($item->id, $userItemIDs)) {
										if ($item->allowable_classes || ($item->allowable_races && ($item->allowable_races == $display->restricted_races))) {
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