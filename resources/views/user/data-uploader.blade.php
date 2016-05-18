@extends('layouts.app')

@section('css')
	<link href="{{ asset('css/fileupload/jquery.fileupload.css') }}" rel="stylesheet">
	<link href="{{ asset('css/user.css') }}" rel="stylesheet">
@stop

@section('js')
	<script src="{{ asset('js/fileupload/vendor/jquery.ui.widget.js') }}"></script>
	<script src="{{ asset('js/fileupload/jquery.iframe-transport.js') }}"></script>
	<script src="{{ asset('js/fileupload/jquery.fileupload.js') }}"></script>
	<script src="{{ asset('js/uploader.js') }}"></script>
@stop

@section('content')
<div class="container-fluid">
	<div class="col-md-10 col-md-offset-1">
		<div class="page-header"><h1>Data Upload</h1></div>
		<div class="uploader row">
			<div class="col-md-2">
				{!! Form::open(['url' => 'upload-data']) !!}
					<span id="data-upload-button" class="btn btn-primary fileinput-button" data-default-text="Upload .lua File">
				        <i class="fa fa-btn fa-upload"></i>
				        <span>&nbsp;Upload .lua file</span>
				        <input id="luaupload" type="file" name="file" />
				    </span>
			    {!! Form::close() !!}
		    </div>
		    <div class="col-md-10">
				<p class="form-control-static status default">File is located at <strong>\World of Warcraft\WTF\Account\{ACCOUNT NAME}\SavedVariables\ItemCollector.lua</strong></p>
				<p class="form-control-static status error">An error occurred.</strong></p>
				<div id="upload-progress" class="progress">
					<div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
						<div class="progress-label"></div>
					</div>
				</div>
				
				<div id="status-msg" class="text-center"></div>
			</div>
		</div>
	</div>
</div>
@endsection