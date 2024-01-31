@extends('admin.layouts.app')

@section('css')
    <!-- BEGIN PAGE LEVEL STYLES -->
    <link href="{{ asset('assets/admin/plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/plugins/select2/select2.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/plugins/select2/select2-metronic.css') }}"/>
    {{--    <link rel="stylesheet" href="{{ asset('assets/admin/plugins/data-tables/DT_bootstrap.css') }}"/>--}}

    <!-- END PAGE LEVEL STYLES -->
@stop
@section('content')
    <!-- BEGIN PAGE HEADER-->
    @include('admin.partials.errors')
    <div class="row">
        <div class="col-md-12">
            <!-- BEGIN PAGE TITLE & BREADCRUMB-->
            <h3 class="page-title">{{ $pageTitle ?? '' }} <small></small></h3>
        {{ Breadcrumbs::render('customer-form.index') }}
        <!-- END PAGE TITLE & BREADCRUMB-->
        </div>
    </div>
    <!-- END PAGE HEADER-->

    <!-- BEGIN PAGE CONTENT-->
    <div class="row">
        <div class="col-md-12">




            <!-- BEGIN EXAMPLE TABLE PORTLET-->
            <div class="portlet-body">

                <h4>&nbsp;</h4>
                <form method="POST" action="{{ route('customer.mail') }}" class="form-horizontal" role="form">
                    @csrf
                    @method('POST')
                    <div class="form-group">
                        <label for="name" class="col-md-2 control-label">Select Type</label>
                        <div class="col-md-4">
                            <select class="form-control col-md-7 col-xs-12" name="type" id="Select-list" required="">

                                <option value="all-customer"> All Customers</option>

                                <option value="selected-customer"> Send To Customers</option>
                            </select>
                        </div>
                        @if ($errors->has('type'))
                            <span class="help-block">
                                        <strong>{{ $errors->first('type') }}</strong>
                                    </span>
                        @endif
                    </div>

                    <div class="form-group" id="customer-list-id" style="display: none">
                        <label for="customers_list" class="col-md-2 control-label">Select Customer</label>
                        <div class="col-md-4">
                            <select class="form-control js-example-basic-multiple col-md-7 col-xs-12"
                                    name="customers_list[]" multiple="multiple" id="customerSelect">
                                @foreach($customer_list as $list)
                                    <option value="{{ $list->id }}"> {{ $list->first_name }} </option>
                                @endforeach
                            </select>
                        </div>
                        @if ($errors->has('customers_list'))
                            <span class="help-block">
                                        <strong>{{ $errors->first('customers_list') }}</strong>
                                    </span>
                        @endif
                    </div>


                    <div class="form-group" id="show-hide">
                        <label for="email" class="col-md-2 control-label">Email</label>
                        <div class="col-md-4">
                            <input type="radio" name="radio" value="email" required="required" />
                        </div>
                        @if ($errors->has('radio'))
                            <span class="help-block">
                                        <strong>{{ $errors->first('radio') }}</strong>
                                    </span>
                        @endif
                    </div>

                    <div class="form-group" id="subjectClass">
                        <label for="subject" class="col-md-2 control-label">Subject</label>
                        <div class="col-md-4">
                            <input type="text" id="subject" name="subject" class="form-control" required="required" />
                        </div>
                        @if ($errors->has('subject'))
                            <span class="help-block">
                                        <strong>{{ $errors->first('subject') }}</strong>
                                    </span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="message" class="col-md-2 control-label">Message Here</label>
                        <div class="col-md-4">
                            <input type="text" id="message" name="message" class="form-control" rows="4" cols="50" required="required"/>
                           {{-- <textarea class="form-control" name="message" rows="4" cols="50" required="required">

                            </textarea>--}}
                        </div>
                        @if ($errors->has('message'))
                            <span class="help-block">
                                        <strong>{{ $errors->first('message') }}</strong>
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
            </div>
            <!-- END EXAMPLE TABLE PORTLET-->
        </div>
    </div>
    <!-- END PAGE CONTENT-->
@stop

@section('footer-js')
    <!-- BEGIN PAGE LEVEL PLUGINS -->
    <script type="text/javascript" src="{{ asset('assets/admin/plugins/select2/select2.min.js') }}"></script>
    <script src="{{ asset('assets/admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/admin/plugins/datatables/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/admin/plugins/datatables/dataTables.bootstrap.min.js') }}"></script>
    <!-- END PAGE LEVEL PLUGINS -->
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="{{ asset('assets/admin/scripts/core/app.js') }}"></script>
    <script src="{{ asset('assets/admin/scripts/custom/user-administrators.js') }}"></script>

    <script>

        jQuery(document).ready(function () {
            // initiate layout and plugins
            App.init();
            Admin.init();
            $('#cancel').click(function () {
                window.location.href = "{{route('dashboard.index') }}";
            });
        });


    </script>
@stop
