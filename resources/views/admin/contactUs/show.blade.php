@extends('admin.layouts.app')

@section('content')
    <!-- BEGIN PAGE HEADER-->
    <div class="row">
        <div class="col-md-12">
            <!-- BEGIN PAGE TITLE & BREADCRUMB-->
            <h3 class="page-title">{{ $pageTitle }} <small></small></h3>
        {{ Breadcrumbs::render('contact-us.show', $contact_us) }}
        <!-- END PAGE TITLE & BREADCRUMB-->
        </div>
    </div>
    <!-- END PAGE HEADER-->

    <!-- BEGIN PAGE CONTENT-->
    <div class="row">
        <div class="col-md-12">

        @include('admin.partials.errors')

        <!-- BEGIN SAMPLE FORM PORTLET-->
            <div class="portlet box blue">

                <div class="portlet-title">
                    <div class="caption">
                        <i class="fa fa-eye"></i> {{ $pageTitle }}
                    </div>
                </div>

                <div class="portlet-body">

                    <h4>&nbsp;</h4>

                    <div class="form-horizontal" role="form">
                        <div class="form-group">
                            <label class="col-md-2 control-label" style="margin-top: -7px"><strong>Name:</strong> </label>
                            <div class="col-md-4">
                                <label>{{ $contact_us->name }}</label>
                            </div>

                            <label class="col-md-2 control-label" style="margin-top: -7px"><strong>Phone:</strong> </label>
                            <div class="col-md-4">
                                <label>{{ $contact_us->phone_formatted }}</label>
                            </div>

                        </div>
                        <div class="form-group">

                            <label class="col-md-2 control-label" style="margin-top: -7px"><strong>Email:</strong> </label>
                            <div class="col-md-4">
                                <label>{{ $contact_us->email }}</label>
                            </div>


                        </div>

                        <div class="form-group">

                            <label class="col-md-2 control-label" style="margin-top: -7px"><strong>Message:</strong> </label>
                            <div class="col-md-4">
                                <label>{{ $contact_us->message }}</label>
                            </div>
                        </div>



                        <div class="form-group">


                            {{--<label class="col-md-2 control-label"><strong>Status:</strong> </label>--}}
                            {{--<div class="col-md-4">--}}
                                {{--<label class="col-md-2 control-label">--}}

                                    {{--@if($sub_admin->is_active == 1)--}}
                                        {{--<span class="btn btn-success">Active</span>--}}
                                    {{--@else--}}
                                        {{--<span class="btn btn-warning">InActive</span>--}}
                                    {{--@endif--}}
                                {{--</label>--}}
                            {{--</div>--}}


                        </div>
                        <div class="form-group">

                        </div>

                        <div class="form-group">
                            <div class="col-md-offset-2 col-md-10">
                                <button class="btn black" id="cancel"
                                        onclick="window.location.href = '{!! URL::route('contact-us.index') !!}'">
                                    Back..
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <!-- END SAMPLE FORM PORTLET-->
        </div>
    </div>
    <!-- END PAGE CONTENT-->
@stop

@section('footer-js')
    <script src="{{ asset('assets/admin/scripts/core/app.js') }}"></script>
    <script>
        jQuery(document).ready(function () {
            // initiate layout and plugins
            App.init();
            Admin.init();
        });
    </script>
@stop
