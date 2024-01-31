@extends('admin.layouts.app')

@section('css')
    <!-- BEGIN PAGE LEVEL STYLES -->
    <link href="{{ URL::to('assets/admin/plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{!! URL::to('assets/admin/plugins/select2/select2.css') !!}"/>
    <link rel="stylesheet" type="text/css" href="{!! URL::to('assets/admin/plugins/select2/select2-metronic.css') !!}"/>
{{--    <link rel="stylesheet" href="{!! URL::to('assets/admin/plugins/data-tables/DT_bootstrap.css') !!}"/>--}}

    <!-- END PAGE LEVEL STYLES -->
@stop
@section('content')
    <!-- BEGIN PAGE HEADER-->
   {{-- @include('admin.partials.errors')--}}
    <div class="row">
        <div class="col-md-12">
            <!-- BEGIN PAGE TITLE & BREADCRUMB-->
            <h3 class="page-title">{{ $pageTitle }} <small></small></h3>
        {{ Breadcrumbs::render('cms.index') }}
        <!-- END PAGE TITLE & BREADCRUMB-->
        </div>
    </div>
    <!-- END PAGE HEADER-->

    <!-- BEGIN PAGE CONTENT-->
    <div class="row">
        <div class="col-md-12">

            <!-- Action buttons Code Start -->
            <div class="row">
                <div class="col-md-12">
                    <!-- Add New Button Code Moved Here -->
                    <div class="table-toolbar pull-right">
                        <div class="btn-group">
                            <!--<a href="{!! URL::route('role.create') !!}" id="sample_editable_1_new"
                               class="btn orange">
                                Add <i class="fa fa-plus"></i>
                            </a>-->
                        </div>
                    </div>
                    <!-- Add New Button Code Moved Here -->
                </div>
            </div>
            <!-- Action buttons Code End -->



        <!-- BEGIN EXAMPLE TABLE PORTLET-->
            <div class="portlet box blue">

                <div class="portlet-title">
                    <div class="caption">
                        {{ $pageTitle }}
                    </div>
                </div>

                <div class="portlet-body">




                    <table id="cms" class="table table-striped table-bordered table-hover" >
                        <thead>
                        <tr>
                            <th class="text-center " style="width:10% ;min-width: 50px!important;">ID</th>
                            <th class="text-center " style="width:10%;min-width: 85px!important;">Page Title</th>
                            <th class="text-center " style="width:10%;min-width: 65px!important;">Slug</th>
                            <th class="text-center " style="width:60%;">Content</th>
                            <th class="text-center" style="width:10%;min-width: 65px !important;">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($Pages as $record)
                            <tr>
                                <td class="text-center">{{$record->id}}</td>
                                <td class="text-center">{{$record->page_title}}</td>
                                <td class="text-center">{{$record->slug}}</td>
                                <td class="text-center">{{$record->content}}</td>
                                <td class="text-center ">@include('admin.cms.action',$record)</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- END EXAMPLE TABLE PORTLET-->
        </div>
    </div>
    <!-- END PAGE CONTENT-->
@stop

@section('footer-js')
    <!-- BEGIN PAGE LEVEL PLUGINS -->
    <script type="text/javascript" src="{!! URL::to('assets/admin/plugins/select2/select2.min.js') !!}"></script>
{{--   <script type="text/javascript"--}}
{{--            src="{!! URL::to('assets/admin/plugins/data-tables/jquery.dataTables.js') !!}"></script>--}}
{{--    <script type="text/javascript" src="{!! URL::to('assets/admin/plugins/data-tables/DT_bootstrap.js') !!}"></script>--}}

    <script src="{{ URL::to('assets/admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::to('assets/admin/plugins/datatables/dataTables.buttons.min.js') }}"></script>
    <script src="{{ URL::to('assets/admin/plugins/datatables/dataTables.bootstrap.min.js') }}"></script>
    <!-- END PAGE LEVEL PLUGINS -->
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="{!! URL::to('assets/admin/scripts/core/app.js') !!}"></script>
    <script src="{!! URL::to('assets/admin/scripts/custom/user-administrators.js') !!}"></script>

    <script>

        jQuery(document).ready(function() {
            // initiate layout and plugins
            App.init();
            Admin.init();
            $('#cancel').click(function() {
                window.location.href = "{!! URL::route('cms.index') !!}";
            });

            appConfig.set( 'dt.searching', true );
            appConfig.set('yajrabox.scrollx_responsive',true);
            $('#cms').DataTable( {
                "scrollX": true
            } );

        });





    </script>
@stop
