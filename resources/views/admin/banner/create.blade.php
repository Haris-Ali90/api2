@extends('admin.layouts.app')

@section('content')
    <!-- BEGIN PAGE HEADER-->
    <div class="row">
        <div class="col-md-12">
            <!-- BEGIN PAGE TITLE & BREADCRUMB-->
            <h3 class="page-title">{{ $pageTitle }} <small></small></h3>
        {{ Breadcrumbs::render('banner.create') }}
        <!-- END PAGE TITLE & BREADCRUMB-->
        </div>
    </div>
    <!-- END PAGE HEADER-->

    <!-- BEGIN PAGE CONTENT-->
    <div class="row">
        <div class="col-md-12">

{{--        @include('admin.partials.errors')--}}

        <!-- BEGIN SAMPLE FORM PORTLET-->
            <div class="portlet box blue">

                <div class="portlet-title">
                    <div class="caption">
                        <i class="fa fa-plus"></i> {{ $pageTitle }}
                    </div>
                </div>

                <div class="portlet-body">

                    <h4>&nbsp;</h4>

                    <form method="POST" action="{{ route('banner.store') }}" class="form-horizontal"
                          role="form" enctype="multipart/form-data">
                        @csrf
                        @method('POST')

                        <div class="form-group">
                            <label for="detail" class="col-md-2 control-label">*Banner Detail</label>
                            <div class="col-md-4">
                                <input type="textarea" name="detail" value="{{ old('detail') }}"
                                       class="form-control"/>
                            </div>
                            @if ($errors->has('detail'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('detail') }}</strong>
                                    </span>
                            @endif
                        </div>
                        <div class="form-group" >
                            {{ Form::label('upload_file', '*Upload Picture', ['class'=>'col-md-2 control-label']) }}
                            <div class="col-md-4">
                                {{ Form::file('upload_file', ['class' => 'form-control ','required' => 'required','onchange'=> 'loadFile(event)']) }}
                                <img id="image" style="max-width: 350px;height: 150px;" onClick="preview(this);"  src="" />
                            </div>
                            @if ( $errors->has('upload_file') )
                                <p class="help-block">{{ $errors->first('upload_file') }}</p>
                            @endif
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
    <script type="text/javascript" src="{{ asset('assets/admin/plugins/ckeditor/ckeditor.js') }}"></script>
    <script src="{{ asset('assets/admin/scripts/core/app.js') }}"></script>
    <script>
        jQuery(document).ready(function () {
            // initiate layout and plugins
            App.init();
            Admin.init();
            $('#cancel').click(function () {
                window.location.href = "{{route('dashboard.index') }}";
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

            $('.phone_us').mask('(000) 000-0000', {placeholder: "(___) ___-____"});
        });

    </script>
@stop
