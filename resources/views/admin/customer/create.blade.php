@extends('admin.layouts.app')

@section('content')
    <!-- BEGIN PAGE HEADER-->
    <div class="row">
        <div class="col-md-12">
            <!-- BEGIN PAGE TITLE & BREADCRUMB-->
            <h3 class="page-title">{{ $pageTitle }} <small></small></h3>
        {{ Breadcrumbs::render('customer.create') }}
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

                    <form method="POST" action="{{ route('customer.store') }}" class="form-horizontal"
                          role="form" enctype="multipart/form-data">
                        @csrf
                        @method('POST')

                        <div class="form-group">
                            <label for="first_name" class="col-md-2 control-label">Full Name *</label>
                            <div class="col-md-4">
                                <input type="text" name="first_name" maxlength="150" value="{{ old('first_name') }}"
                                       class="form-control"/>
                            </div>
                            @if ($errors->has('first_name'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('first_name') }}</strong>
                                    </span>
                            @endif
                        </div>

                       {{-- <div class="form-group">
                            <label for="last_name" class="col-md-2 control-label">Last Name *</label>
                            <div class="col-md-4">
                                <input type="text" name="last_name" maxlength="32" value="{{ old('last_name') }}"
                                       class="form-control"/>
                            </div>
                            @if ($errors->has('last_name'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('last_name') }}</strong>
                                    </span>
                            @endif
                        </div>--}}

                        <div class="form-group">
                            <label for="email" class="col-md-2 control-label">Email *</label>
                            <div class="col-md-4">
                                <input type="text" name="email" maxlength="32" value="{{ old('email') }}"
                                       class="form-control"/>
                            </div>
                            @if ($errors->has('email'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="phone" class="col-md-2 control-label">Phone *</label>
                            <div class="col-md-4">
                                <input type="text" name="phone" maxlength="32" value="{{ old('phone') }}"
                                       class="form-control phone_us"/>
                            </div>
                            @if ($errors->has('phone'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('phone') }}</strong>
                                    </span>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="rights" class="col-md-2 control-label">Role Type *</label>
                            <div class="col-md-4">
                                <select class="form-control col-md-7 col-xs-12 role-type" name="role" required>
                                    <option value="">Select an option</option>
                                    @foreach($role_list as $role)
                                        <option value="{{$role->id}}">{{$role->display_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($errors->has('role'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('role') }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="password" class="col-md-2 control-label">Password *</label>
                            <div class="col-md-4">
                                <input type="password" name="password" maxlength="32" value=""
                                       class="form-control"/>
                            </div>
                            @if ($errors->has('password'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="col-md-2 control-label">Confirm Password *</label>
                            <div class="col-md-4">
                                <input type="password" name="confirm_password" value="" class="form-control" />
                            </div>
                            @if ($errors->has('confirm_password'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('confirm_password') }}</strong>
                                    </span>
                            @endif
                        </div>
                        <div class="form-group" >
                            {{ Form::label('upload_file', 'Upload Picture *', ['class'=>'col-md-2 control-label']) }}
                            <div class="col-md-4">
                                {{ Form::file('upload_file', ['class' => 'form-control ','required' => 'required']) }}
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
                window.location.href = "{{route('customer.index') }}";
            });
        });

        $(document).ready(function() {

            $('.phone_us').mask('(000) 000-0000', {placeholder: "(___) ___-____"});
        });
    </script>
@stop
