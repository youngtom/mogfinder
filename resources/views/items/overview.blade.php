@extends('layouts.app')

@section('content')
<div class="container-fluid">
	<?php foreach ($categories as $group => $categoryArr) { ?>
	<div class="page-header"><h1><?=ucwords($group)?></h1></div>
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
			        <a href="<?=url('/wardrobe/' . $group . '/' . $category->url_token . '/' . $mogslot->simple_url_token)?>" class="mogslot-progress-wrapper">
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
    <?php } ?>
</div>
@endsection