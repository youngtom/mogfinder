@extends('layouts.app')

@section('content')
<div class="container-fluid">
	<div class="page-header"><h1><?=$mogslot->label?></h1></div>
	<?php
		$icons = [];
		foreach ($itemDisplays as $display) { 
			foreach ($display->items as $item) {
				if ($item->icon_image_id && !in_array($item->icon_image_id, $icons)) {
					$icons[] = $item->icon_image_id;
					echo '<a href="' . url('/items/set-mogslot-icons/' . $mogslot->id . '/' . $item->icon_image_id) . '"><img src="' . $item->getFile('icon_image')->getWebPath() . '" /></a>';
				}
			}
		}
	?>
</div>
@endsection