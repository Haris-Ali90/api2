@extends('admin.layouts.app')

@section('content')
    <!-- BEGIN PAGE HEADER-->
    <div class="row">
        <div class="col-md-12">
            <!-- BEGIN PAGE TITLE & BREADCRUMB-->
            <h3 class="page-title">{{ $pageTitle }} <small></small></h3>
        {{ Breadcrumbs::render('role.show', $role) }}
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
                        <i class="fa fa-search"></i> {{ $pageTitle }}
                    </div>
                </div>

                <!--portlet-body-open-->
                <div class="portlet-body details-page-main-container">

                    <!--show-details-title-open-->
                    <div class="col-sm-12 show-title">
                        <h4>Joey Plan Main Details</h4>
                    </div>
                    <!--show-details-box-close-->

                    <!--details-page-wrap-open-->
                    <div class="row  details-page-wrap">
                        <!--show-details-box-open-->
                        <div class="col-sm-3 show-details-box">
                            <p class="show-details-lable">Name:</p>
                            <p class="show-details-para">{{$role->display_name}}</p>
                        </div>
                        <!--show-details-box-close-->

                        <!--show-details-box-open-->
                        <div class="col-sm-3 show-details-box">
                            <p class="show-details-lable">Created at:</p>
                            <p class="show-details-para">{{$role->created_at}}</p>
                        </div>
                        <!--show-details-box-close-->

                        <!--show-details-box-open-->
                        <div class="col-sm-3 show-details-box">
                            <p class="show-details-lable">Total users count:</p>
                            <p class="show-details-para">{{$role->User->count()}}</p>
                        </div>
                        <!--show-details-box-close-->

                        <!--show-details-box-open-->
                        <div class="col-sm-3 show-details-box">
                            <p class="show-details-lable">Total permissions #:</p>
                            <p class="show-details-para total-permission-count">{{$role->Permissions->count()}}</p>
                        </div>
                        <!--show-details-box-close-->
                    </div>
                    <!--details-page-wrap-close-->

                    <!--show-details-title-open-->
                    <div class="col-sm-12 show-title">
                        <h4>Permissions list</h4>
                    </div>
                    <!--show-details-box-close-->

                    <!--details-page-wrap-open-->
                    <div class="row  details-page-wrap">
                        <!--show-details-box-open-->
                        <div class="col-sm-12 show-details-box">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                    <tr>
                                        <th class="text-center ">#</th>
                                        <th class="text-center">Type</th>
                                        <th class="text-center">Access</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-cente">0</td>
                                            <td class="text-center">Dashboard Cards Rights</td>
                                            <td class="text-center">
                                                {{ucfirst(str_replace(',',' , ',str_replace('_',' ',$role->dashbaord_cards_rights)))}}
                                            </td>
                                        </tr>
                                        @php $permission_count = 0; @endphp
                                        @foreach($permissions as $type => $permission )
                                        <tr>
                                            <td class="text-cente">{{$loop->iteration}}</td>
                                            <td class="text-center">{{$type}}</td>
                                            <td class="text-center">
                                                    @foreach($permission as $name => $permission)
                                                        @if(check_permission_exist($permission,$route_names))
                                                            {{$name}} ,
                                                            @php $permission_count++; @endphp
                                                        @endif
                                                    @endforeach
                                            </td>
                                        </tr>
                                    @endforeach

                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!--show-details-box-close-->

                    </div>
                    <!--details-page-wrap-close-->




                </div>
                <!--portlet-body-close-->
            </div>
            <!-- END SAMPLE FORM PORTLET-->
        </div>
    </div>
    <!-- END PAGE CONTENT-->

@stop

@section('footer-js')
    <script src="{!! URL::to('assets/admin/scripts/core/app.js') !!}"></script>
    <script>
        jQuery(document).ready(function () {
            // initiate layout and plugins
            App.init();
            Admin.init();
            // setting total permission count
            $('.total-permission-count').text("{{$permission_count}}");
        });
    </script>
@stop
