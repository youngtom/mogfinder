@extends('layouts.app')

@section('content')
<div class="container">
	<div class="col-md-10 col-md-offset-1">
	    <div class="row">
		    <div class="jumbotron">
			    <h1>ItemCollector v<?=$currentModVersion?></h1>
			    <p>This addon is used to scan items obtained on your account. To use it, just log in to each character you wish to scan, open your bank and void storage (and open the heirloom tab on one character).</p>
			    <p>Type /mcc to show a summary of what has been scanned.</p>
			    <p>To scan the currently open guild bank tab, type /mcc gb (/mcc gbclear will clear all of your current guild's bank data).</p>
			    <p>
				    <a class="btn btn-primary btn-lg" href="{{ asset('/files/ItemCollector.zip') }}" role="button">Download v<?=$currentModVersion?></a>
				    <a class="btn btn-info btn-lg" href="http://mods.curse.com/addons/wow/itemcollector" target="_blank" role="button">Download on Curse.com</a>
			    </p>
		    </div>
		</div>
		<div class="row">
			<dl class="dl-horizontal">
				<dd><h2>Addon Change History</h2></dd>
				<dt>2.1.0</dt>
				<dd>Added guild bank scanning. "/mcc gb" to scan the current tab of the guild bank. "/mcc gbclear" to clear current guild data.</dd>
				<dd>Added completed quest scanning</dd>
				<dt>2.0.5</dt>
				<dd>Added missing library (CallbackHandler-1.0)</dd>
				<dt>2.0.4</dt>
				<dd>Fixed several issues that were caused by throttling scanning.</dd>
				<dd>Improved event-driven scanning performance.</dd>
				<dt>2.0.3</dt>
				<dd>Fixed problem with void storage not being completely scanned at times.</dd>
				<dt>2.0.2</dt>
				<dd>Minor code cleanup</dd>
				<dt>2.0.1</dt>
				<dd>Fixed remaining mod conflict issues by implementing AceAddon-3.0 libraries.</dd>
				<dt>2.0.0</dt>
				<dd>Initial public release.</dd>
				<dd>Rewrite to scan based on events, rather than a timer.</dd>
				<dt>1.0.0</dt>
				<dd>Initial test addon for dupicate scanning</dd>
			</dl>
		</div>
	</div>
</div>
@endsection
