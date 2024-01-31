@extends('admin.layouts.login')

@section('content')

@if (Session::get('error'))
    <div class="alert alert-danger">{!! Session::get('error') !!}</div>
@endif

@if (Session::get('status'))
    <div class="alert alert-success">{!! Session::get('status') !!}</div>
@endif

<!-- BEGIN FORGOT PASSWORD FORM -->
<form method="POST" action="{{ route('forgot-password')  }}">
    @csrf

    <input type="hidden" name="is_admin" value="1" />

    <h3>Forgot your password?</h3>
    <p>Enter your e-mail address below to reset your password.</p>

    @if (isset($errors) && count($errors) > 0)
        <div class="alert alert-danger custom">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
            @foreach ($errors->all() as $error)
                {{ $error }}
            @endforeach
        </div>
    @endif

    @if (Session::get('success'))
        <div class="alert alert-success">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
            {{ Session::get('success') }}
        </div>
    @endif

    <div class="form-group">
        <div class="input-icon">
            <i class="fa fa-envelope"></i>
            <input type="text" name="email" id="email" class="form-control placeholder-no-fix" />
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('login')  }}" class="btn btn-default"><i class="m-icon-swapleft"></i> Back </a>
        <button type="submit" class="btn green pull-right">Submit <i class="m-icon-swapright m-icon-white"></i></button>
    </div>

</form>
<!-- END FORGOT PASSWORD FORM -->

@stop
