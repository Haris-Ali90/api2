@extends('admin.layouts.app')

@section('content')
<!-- BEGIN PAGE HEADER-->
<div class="row">
    <div class="col-md-12">
        <!-- BEGIN PAGE TITLE & BREADCRUMB-->
        <h3 class="page-title">{{ $pageTitle }} <small></small></h3>
        {{ Breadcrumbs::render('users.profile') }}
        <!-- END PAGE TITLE & BREADCRUMB-->
    </div>
</div>
<!-- END PAGE HEADER-->
<link href="{{ asset('assets/admin/css/customPreview.css') }}" rel="stylesheet" type="text/css"/>
<!-- BEGIN PAGE CONTENT-->
<div class="row">
    <div class="col-md-12">
{{--
        @include('admin.partials.errors')--}}

        <!-- BEGIN SAMPLE FORM PORTLET-->
        <div class="portlet box blue">

            <div class="portlet-title">
                <div class="caption">
                    <i class="fa fa-edit"></i> {{ $pageTitle }}
                </div>
            </div>

            <div class="portlet-body">

                <h4>&nbsp;</h4>

                <form method="POST" action="{{ route('users.edit-profile') }}" class="form-horizontal" role="form" enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <div class="form-group">
                        <label for="first_name" class="col-md-2 control-label">*Full Name</label>
                        <div class="col-md-4">
                            <input type="text" name="first_name" maxlength="32" value="{{ old('first_name', $data->getFullname()) }}" class="form-control" />
                            @if ($errors->has('first_name'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('first_name') }}</strong>
                                    </span>
                            @endif
                        </div>


                     {{--   <label for="last_name" class="col-md-2 control-label">Last Name *</label>
                        <div class="col-md-4">
                            <input type="text" name="last_name" maxlength="32" value="{{ old('last_name', $data->last_name) }}" class="form-control" />
                        </div>--}}
                        <label for="email" class="col-md-2 control-label">*Email</label>
                        <div class="col-md-4">
                            <input type="text" name="email" maxlength="128" value="{{ old('email', $data->email) }}" class="form-control" readonly />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="username" class="col-md-2 control-label ">Phone </label>
                        <div class="col-md-4">
                            <input type="text" name="phone" maxlength="24" value="{{ old('phone', $data->phone) }}" class="form-control phone_us" />
                        </div>

                        <label for="password" class="col-md-2 control-label">Password</label>
                        <div class="col-md-4">
                            <input type="password" name="password" maxlength="30"  class="form-control" />
                        </div>
                    </div>

                    <div class="form-group">



                        <label for="profile_picture" class="col-md-2 control-label">Profile Picture </label>
                        <div class="col-md-4">

                            <input type="file" onchange="loadFile(event)" name="profile_picture" class="form-control" />
                                <br>
{{--                                <img style="border: solid;" src="{!! backendUserUrl($data->profile_picture_auto) !!}" alt="" title="{!! $data->full_name !!}" class="img-responsive" width="150" height="150" /><br>--}}
                            <img id="image" style="border: solid;" onClick="preview(this);" src="{{$data->profile_picture}}" alt="" title="{!! $data->full_name !!}" class="img-responsive" width="150" height="150" /><br>
                        </div>

                    </div>



                    <div class="form-group">
                        <div class="col-md-offset-2 col-md-10">
                            <input type="submit" class="btn blue" id="save" value="Save">
                            <input type="button" class="btn black" name="cancel" id="cancel" value="Cancel">
                        </div>
                    </div>
                </form>
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
<script src="{{ asset('assets/admin/scripts/custom/customPreview.js') }}"></script>
<script>
jQuery(document).ready(function() {
   // initiate layout and plugins
   App.init();
   Admin.init();
   $('#cancel').click(function() {
        window.location.href = "{!! URL::route('dashboard.index') !!}";
   });
});
var loadFile = function(event) {
    var reader = new FileReader();
    reader.onload = function(){
        var output = document.getElementById('image');
        output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
};


$(document).ready(function() {
//    $('.phone_us').mask('(000) 000-0000', {placeholder: "(___) ___-____"});
});



</script>
@stop
