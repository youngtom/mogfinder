@extends('layouts.app')

@section('js')
	<script src="{{ asset('js/items.js') }}"></script>
@stop

@section('content')
<?=implode($newline, $out)?>
@endsection
