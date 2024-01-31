@if (isset($errors) && count($errors) > 0)
    <div class="alert alert-danger custom">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
        @foreach ($errors->all() as $error)
            {{ $error }}<br />
        @endforeach
    </div>
@endif

@if (Session::has('error'))
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
        {!! Session::pull('error') !!}
    </div>
@endif

@if (Session::has('success'))
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
        {!! Session::pull('success') !!}
    </div>
@endif