@extends('layouts.app')

@section('content')
<?php
	$urlAppend = ($selectedCharacter && $selectedCharacter->charClass && $selectedCharacter->faction) ? '#class:' . $selectedCharacter->charClass->url_token . ';faction:' . strtolower($selectedCharacter->faction->name) : '';
?>
<div class="container-fluid">
	<?php 
		$count = 0;
		foreach ($categories as $group => $categoryArr) {
	?>
	<div class="page-header">
		<?php if (count($characters) && !$count++) { ?>
		<div class="btn-group pull-right" role="group">
			<button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<?php if ($selectedCharacter) { ?>
				<i class="game-icon-sm" style="background-image: url(<?=($selectedCharacter->charClass) ? $selectedCharacter->charClass->getFile('icon_image')->getWebPath() : ''?>)"></i> <?=$selectedCharacter->name?> <span class="realm-name"> - <?=$selectedCharacter->realm->name?> (<?=$selectedCharacter->realm->region?>) <span class="caret"></span>
				<?php } else { ?>
				<span class="selected-value">Select a character</span> <span class="caret"></span>
				<?php } ?>
			</button>
			<ul class="dropdown-menu scrollable-menu">
				<?php if ($selectedCharacter) { ?>
				<li class="all-select"><a href="<?=url('wardrobe/')?>" data-class-id="0" class="show-all">Show all</a></li>
				<li class="all-select divider" roll="separator"></li>
				<?php } ?>
			<?php 
					foreach ($characters as $realmID => $characterArr) {
			?>
				<li class="dropdown-header"><?=$characterArr[0]->realm->name?> (<?=$characterArr[0]->realm->region?>)</li>
			<?php
						foreach ($characterArr as $character) {
			?>
				<li>
					<a href="<?=url('wardrobe/' . $character->url_token)?>" data-character-id="<?=$character->id?>">
						<i class="game-icon-sm" style="background-image: url(<?=($character->charClass) ? $character->charClass->getFile('icon_image')->getWebPath() : ''?>)"></i> <?=$character->name?></span>
					</a>
				</li>
			<?php 
					}
				}
			?>
			</ul>
		</div>
		<?php } ?>
		<h1><?=ucwords($group)?></h1>
	</div>
    <div class="row">
        <?php foreach ($categoryArr as $category) { ?>
        <div class="col-sm-6 col-md-4">
	        <div class="mogslot-summary thumbnail">
		        <div class="caption">
			        <h3><?=$category->label?></h3>
			        <?php
				        foreach ($mogslotsByCategory[$category->id] as $mogslot) {
					       if ($totalMogslotCounts[$mogslot->id]) {
						       $userCount = $userMogslotCounts[$mogslot->id];
						       $percent = round($userMogslotCounts[$mogslot->id] / $totalMogslotCounts[$mogslot->id], 2) * 100;
				    ?>
			        <a href="<?=url('/wardrobe/' . $group . '/' . $category->url_token . '/' . $mogslot->simple_url_token) . $urlAppend?>" class="mogslot-progress-wrapper">
			        	<i class="game-icon-sm" style="background-image: url(<?=($mogslot->icon_image_id) ? $mogslot->getFile('icon_image')->getWebPath() : ''?>)"></i>
			        	<div class="progress">
				        	<div class="progress-bar" role="progressbar" aria-valuenow="<?=$userMogslotCounts[$mogslot->id]?>" aria-valuemin="0" aria-valuemax="<?=$totalMogslotCounts[$mogslot->id]?>" style="width: <?=$percent?>%;"></div>
				        	<div class="progress-label summary"><?=$userMogslotCounts[$mogslot->id]?> / <?=$totalMogslotCounts[$mogslot->id]?></div>
				        	<div class="progress-label mogslot-info"><?=$mogslot->simple_label?></div>
			        	</div>
			        </a>
			        <?php
				        	}
				        }
				    ?>
		        </div>
	        </div>
        </div>
        <?php } ?>
    </div>
    <?php
	    }
	?>
</div>
@endsection