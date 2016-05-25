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
        <div class="col-md-10 col-md-offset-1 <?=(count($itemDisplays) > 1) ? 'item-scroll-with-nav' : ''?>">
	        <?php if (count($itemDisplays) > 1) { ?>
	        @include('items.shared.display-filter')
			<?php } ?>
			
	        <?php if (@$headerText) { ?>
	        <div class="display-title">
		        <h2><?=$headerText?></h2>
	        </div>
	        <?php } ?>
			
			<div id="no-displays-alert" class="alert alert-info <?=(count($itemDisplays)) ? 'hidden' : ''?>" role="alert">No appearances found matching that <?=(@$search && !count($itemDisplays)) ? 'search. Please try again' : 'filter'?>.</div>
			
			<?php if (@$itemDisplays) { ?>
	        @include('items.shared.display-listing')
	        <?php } ?>
        </div>
    </div>
</div>
@endsection
