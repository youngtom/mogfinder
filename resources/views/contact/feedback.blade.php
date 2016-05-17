@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Feedback</div>
                <div class="panel-body">
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @else

                    {!! Form::open(array('route' => 'feedback_store', 'class' => 'form-horizontal')) !!}
                        {!! csrf_field() !!}
                        
                        <input type="hidden" name="referrer" value="<?=URL::previous()?>" />

                        <div class="form-group{{ $errors->has('subject') ? ' has-error' : '' }}">
                            <label class="col-md-4 control-label">Subject</label>

                            <div class="col-md-6">
                                <input type="text" class="form-control" name="subject" value="{{ old('subject') }}">

                                @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="form-group{{ $errors->has('message') ? ' has-error' : '' }}">
                            <label class="col-md-4 control-label">Message</label>

                            <div class="col-md-6">
                                <textarea class="form-control" name="message" value="{{ old('message') }}" rows="10"></textarea>

                                @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-btn fa-envelope"></i> Send
                                </button>
                            </div>
                        </div>
                    {!! Form::close() !!}
                    
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
