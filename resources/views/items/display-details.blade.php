@extends('layouts.app')

@section('css')
	<link href="{{ asset('css/items.css') }}" rel="stylesheet">
@stop

@section('js')
	<script src="{{ asset('js/items.js') }}"></script>
@stop

@section('content')
<div class="container-fluid">
	<div class="col-md-10 col-md-offset-1">
		<div class="page-header">
			<h1>
				Display <?=$display->id?> - <?=ucwords($display->mogslot->singular_label)?>
				<span class="pull-right">
		        	<?php if ($unlockedClasses && count($unlockedClasses)) { ?>
		        	<i class="fa fa-btn fa-star-o partiallly-collected-star" title="Unlocked on:<br><?=implode('<br>', $unlockedClasses)?>" data-toggle="tooltip" data-placement="left"></i>
		        	<?php } elseif (count($userItems)) { ?>
		        	<i class="fa fa-btn fa-star collected-star" title="Collected" data-toggle="tooltip" data-placement="left"></i>
		        	<?php } ?>
		        </span>
	        </h1>
		</div>
	    <div class="row">
	        <div class="col-md-3 render-col">
		        <?php if ($render = $display->getFile('render_image')) { ?>
		        <div class="item-render-wrapper">
		            <div class="item-display-render" style="background-image: url(<?=$render->getWebPath(280, 280, 'crop', 0, 0)?>)"></div>
		            <img src="<?=asset('img/blank.png')?>" />
		        </div>
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
					?>
						<tr class="item-row">
							<td class="itemname"><a href="http://www.wowhead.com/item=<?=$item->bnet_id?>" target="_blank" rel="<?=$item->getWowheadMarkup()?>" class="item-link q<?=$item->quality?>">[<?=$item->name?>]</a></td>
							<td class="source"><?=$sources?></td>
							<td class="center collected">
							<?php 
								if (in_array($item->id, $userItems->lists('item_id')->toArray())) {
									if ($item->allowable_classes || $item->allowable_races) {
										echo '<i class="fa fa-btn fa-star-o"></i>';
									} else {
										echo '<i class="fa fa-btn fa-star"></i>';
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
	    <?php if ($auctions && count($auctions)) { ?>
	    <div class="row auction-list-row">
		    <div class="panel panel-default item-display-panel">
	            <div class="panel-heading">Current Auctions:</div>
	            <table class="table table-hover auction-list-table">
	                <thead>
	                    <tr>
	                        <th>Item</th>
	                        <th>Realm</th>
	                        <th>Seller</th>
	                        <th>Bid</th>
	                        <th>Buyout</th>
	                    </tr>
	                </thead>
	                <tbody>
					<?php foreach ($auctions as $auction) { ?>
						<tr class="item-row auction-row">
							<td><a href="http://www.wowhead.com/item=<?=$auction->item->bnet_id?>" target="_blank" rel="<?=($auction->bonuses) ? 'bonus=' . str_replace(',', ':', $auction->bonuses) : ''?>" class="item-link q<?=$auction->item->quality?>">[<?=$auction->item->name?>]</a></td>
							<td><?=$auction->realm->name?> (<?=$auction->realm->region?>)</td>
							<td><?=$auction->seller?></td>
							<td><?=$auction->bid ? \App\Auction::formatPrice($auction->bid): '&nbsp;'?></td>
							<td><?=$auction->buyout ? \App\Auction::formatPrice($auction->buyout) : '&nbsp;'?></td>
						</tr>
					<?php } ?>
	                </tbody>
	            </table>
		    </div>
	    </div>
	    <?php } ?>
	</div>
</div>
@endsection