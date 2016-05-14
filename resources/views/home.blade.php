@extends('layouts.app')

@section('content')
<div class="container">
	<div class="col-md-10 col-md-offset-1">
	    <div class="row">
		    <div class="panel panel-default">
			    <div class="panel-body">
			        <div class="page-header">
						<h3>Welcome <small>What is this?</small></h3>
					</div>
					<p>MogCollector is a tool built to assist in collecting gear for the transmog/wardrobe system being introduced in Legion. Currently, this site can be used to determine which displays you've unlocked, and to help find those you have not yet unlocked. There's also a duplicate item finder that can help free up some much-needed bag space. With time, I hope to implement more features that will further assist completionists like myself.</p>
				    <div class="page-header">
					    <h3>Getting Started</h3>
					</div>
					<ol>
						<li>Go to the <a href="{{ url('/register') }}">Registration</a> page and sign up for an account.</li>
						<li>Download and install the <a href="{{ asset('/files/ItemCollector.zip') }}">MogCollector ItemCollector</a> addon.  This mod is used to gather a list of items you have collected. <em>(Note: currently, some mods (Cross Realm Assist and ServerHop) are known to prevent proper collection, so these may need to be disabled in order for it to work)</em></li>
						<li>Log in to each character you wish to scan. Be sure to also open your bank and void storage so that all items can be scanned.</li>
						<li>Go to the <a href="{{ url('/user/upload-data') }}">data uploader</a> page and follow the instructions to upload the generated saved variables file. This process may take awhile, depending on how much data needs to be imported.</li>
					</ol>
					<div class="page-header">
						<h3>Feedback <small>How you can help</small></h3>
					</div>
					<p>If you have any suggestions for site, run into any bugs, or find any items that are missing (or should not be listed) please <a href="#">let me know</a>.</p>
				</div>
		    </div>
		</div>
	</div>
</div>
@endsection
