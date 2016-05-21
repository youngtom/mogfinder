@extends('layouts.app')

@section('content')
<div class="container">
	<div class="col-md-10 col-md-offset-1">
	    <div class="row">
		    <div class="jumbotron">
			    <h1>ItemCollector v2.1.0</h1>
			    <p>This addon is used to scan items obtained on your account. To use it, just log in to each character you wish to scan, open your bank and void storage (and open the heirloom tab on one character). Type /mcc to show a summary of what has been scanned. To scan the currently open guild bank tab, type /mcc gb (/mcc gbclear will clear all of your current guild's bank data).</p>
			    <p>
				    <a class="btn btn-primary btn-lg" href="{{ asset('/files/ItemCollector.zip') }}" role="button">Download v2.1.0</a>
				    <a class="btn btn-info btn-lg" href="http://mods.curse.com/addons/wow/itemcollector" target="_blank" role="button">Download on Curse.com</a>
			    </p>
		    </div>
		</div>
	</div>
</div>
@endsection
