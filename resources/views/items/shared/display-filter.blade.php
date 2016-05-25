<div class="item-filter-nav navbar subnav" role="navigation">
    <div class="navbar-inner">
        <div class="container">
	        <form class="navbar-form">
		        <div class="form-group">
			        <p class="navbar-text">Filter: </p>
			        <div class="btn-group navbar-btn" role="group">
				        <div type="button" class="btn btn-sm btn-primary collected-toggle-btn" data-collected="1">
				        	<i class="fa fa-btn fa-check-square-o"></i><i class="fa fa-btn fa-square-o"></i> &nbsp;Collected&nbsp; <span class="badge collected-count"><?=count($userDisplayIDs)?></span>
				        </div>
				        <div type="button" class="btn btn-sm btn-primary collected-toggle-btn" data-collected="0">
				        	<i class="fa fa-btn fa-check-square-o"></i><i class="fa fa-btn fa-square-o"></i> &nbsp;Not Collected&nbsp; <span class="badge uncollected-count"><?=count($itemDisplays) - count($userDisplayIDs)?></span>
				        </div>
			        </div>
		        </div>
		        
		        <?php if ($classes && count($classes) > 1) { ?>
		        <div class="form-group class-filter-group">
			        <p class="navbar-text">Class:</p>
			        <div class="btn-group navbar-btn class-filter selectable-filter all-selected" role="group">
						<button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<span class="selected-value">All</span> <span class="caret"></span>
						</button>
						<ul class="dropdown-menu">
							<li class="all-select"><a href="javascript:;" data-class-id="0" class="show-all">Show all</a></li>
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
						<button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<span class="selected-value">All</span> <span class="caret"></span>
						</button>
						<ul class="dropdown-menu">
							<li class="all-select"><a href="javascript:;" data-faction-mask="0" class="show-all">Show all</a></li>
							<li class="all-select divider" roll="separator"></li>
						<?php foreach ($factions as $faction) { ?>
							<li>
								<a href="#faction:<?=strtolower($faction->name)?>" data-faction-mask="<?=$faction->race_bitmask?>" data-faction-code="<?=strtolower($faction->name)?>" class="<?=strtolower($faction->name)?>"><i class="game-icon-tiny icon-<?=strtolower($faction->name)?>"></i> <?=$faction->name?></a>
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
						<button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<span class="selected-value">All</span> <span class="caret"></span>
						</button>
						<ul class="dropdown-menu">
							<li class="all-select"><a href="javascript:;" data-source-id="0" class="show-all">Show all</a></li>
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