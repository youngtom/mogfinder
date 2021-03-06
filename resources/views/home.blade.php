@extends('layouts.app')

@section('content')
<div class="container">
	<div class="col-md-10 col-md-offset-1">
	    <div class="row">
		    <div class="panel panel-default">
			    <div class="panel-body">
				    <div class="page-header">
						<h3>Updated for 7.0 &amp; Legion!</h3>
					</div>
					<p>MogCollector has (finally) been updated to support the new wardrobe feature. Be sure to <a href="{{ url('/download') }}">download</a> the latest version of the addon, and get back to collecting!</p>
			        <div class="page-header">
						<h3>Welcome <small>What is this?</small></h3>
					</div>
					<p>MogCollector is a tool built to assist in collecting gear for the transmog/wardrobe system being introduced in Legion. Currently, this site can be used to determine which appearances you've unlocked, and to help find those you have not yet unlocked. With time, I hope to implement more features that will further assist completionists like myself.</p>
				    <div class="page-header">
					    <h3>Getting Started</h3>
					</div>
					<ol>
						<li>Go to the <a href="{{ url('/register') }}">Registration</a> page and sign up for an account.</li>
						<li>Download and install the <a href="{{ url('/download') }}">MogCollector ItemCollector</a> addon.  This mod is used to gather a list of appearances you have collected.</li>
						<li>Log in to each character you wish to scan. Scanning takes place automatically upon logging in, but a manual scan can be uploaded manually.</li>
						<li>Go to the <a href="{{ url('/user/upload-data') }}">data uploader</a> page and follow the instructions to upload the generated saved variables file. This process may take awhile, depending on how much data needs to be imported.</li>
					</ol>
					<div class="page-header">
						<h3>Feedback <small>How you can help</small></h3>
					</div>
					<p>If you have any suggestions for site, run into any bugs, or find any items that are missing (or should not be listed) please <a href="{{ url('/feedback') }}">let me know</a>.</p>
				</div>
		    </div>
		</div>
	</div>
</div>
@endsection
