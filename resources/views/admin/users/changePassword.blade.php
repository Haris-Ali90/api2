@extends('admin.layouts.app')

@section('content')
<!-- BEGIN PAGE HEADER-->
<div class="row">
    <div class="col-md-12">
        <!-- BEGIN PAGE TITLE & BREADCRUMB-->
        <h3 class="page-title">{{ $pageTitle }} <small></small></h3>
        {{ Breadcrumbs::render('users.change-password') }}
        <!-- END PAGE TITLE & BREADCRUMB-->
    </div>
</div>
<!-- END PAGE HEADER-->

<!-- BEGIN PAGE CONTENT-->
<div class="row">
    <div class="col-md-12">

    @include('admin.partials.success')
        <!-- BEGIN SAMPLE FORM PORTLET-->
        <div class="portlet box blue">

            <div class="portlet-title">
                <div class="caption"> <i class="fa fa-edit"></i> {{ $pageTitle }}</div>
            </div>

            <div class="portlet-body">

                <h4>&nbsp;</h4>

                <form method="POST" action="{{ route('users.change-password') }}" class="form-horizontal" role="form">
                    @csrf
                    <div class="form-group">
                        <label for="oldPassword" class="col-md-2 control-label">*Old Password</label>
                        <div class="col-md-4">
                            <input type="password" name="oldPassword" value="{{ old('oldPassword') }}" class="form-control" />
                        </div>
                        @if ($errors->has('oldPassword'))
                            <span class="help-block" style="margin-left: 15px;" >
                                        <strong>{{ $errors->first('oldPassword') }}</strong>
                                    </span>
                        @endif

                    </div>
                    <div class="form-group">
                        <label for="password" class="col-md-2 control-label">*New Password</label>
                        <div class="col-md-4">
                            <input type="password" name="password" value="{{ old('password') }}"class="form-control" />
                        </div>
                        @if ($errors->has('password'))
                            <span class="help-block" style="margin-left: 15px;">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation" class="col-md-2 control-label">*Confirm New Password</label>
                        <div class="col-md-4">
                            <input type="password" name="password_confirmation"  class="form-control"  value="{{ old('password_confirmation') }}" />
                        </div>
                        @if ($errors->has('password_confirmation'))
                            <span class="help-block" style="margin-left: 15px;">
                                        <strong>{{ $errors->first('password_confirmation') }}</strong>
                                    </span>
                        @endif
                    </div>
                    <div class="form-group">
                        <div class="col-md-offset-2 col-md-10">
                            <input type="submit" class="btn blue" id="save" value="Save">
                            <input type="button" class="btn black" name="cancel" id="cancel" value="Cancel">
                        </div>
                    </div>
                </form>

                <h4>&nbsp;</h4>

            </div>
        </div>
        <!-- END SAMPLE FORM PORTLET-->
    </div>
</div>
<!-- END PAGE CONTENT-->
@stop

@section('footer-js')
<script type="text/javascript" src="{!! URL::to('assets/admin/plugins/ckeditor/ckeditor.js') !!}"></script>
<script src="{!! URL::to('assets/admin/scripts/core/app.js') !!}"></script>
<script>
jQuery(document).ready(function() {
   // initiate layout and plugins
   App.init();
   Admin.init();
   $('#cancel').click(function() {
        window.location.href = "{!! URL::route('dashboard.index') !!}";
   });
});
</script>
@stop
