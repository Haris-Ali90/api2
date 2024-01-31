@extends('admin.layouts.app')


@section('css')
    <style>
        .detail-form-wrap-1, .detail-form-wrap-2
        {
            margin-left: 0px;
            margin-right: 0px;
        }
        .detail-form-wrap-h4 {
            font-size: 14px;
            background-color: #3d3d3d;
            padding: 5px 10px;
            color: #fff;
        }
        .permission{
            font-size: 12px;
        }
    </style>
@stop

@section('content')
    <!-- BEGIN PAGE HEADER-->
    <div class="row">
        <div class="col-md-12">
            <!-- BEGIN PAGE TITLE & BREADCRUMB-->
            <h3 class="page-title">{{ $pageTitle }} <small></small></h3>
        {{ Breadcrumbs::render('role.set-permissions',$role) }}
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
                        <i class="fa fa-plus"></i> {{ $pageTitle }}
                    </div>
                </div>

                <div class="portlet-body">

                    <h4>&nbsp;</h4>

                    <form method="POST"  action="{{ route('role.set-permissions.update',$role->id) }}" class="form-horizontal"
                          role="form" enctype="multipart/form-data">
                        @csrf
                        @method('POST')

                        @foreach($permissions_list as $permission_label => $permissions)
                        <!--from-input-wraper-open-->
                        <div class="row from-input-wraper">

                            <div class="col-md-12">
                                <p class="section-heading">{{$permission_label}}</p>
                            </div>

                            @foreach($permissions as  $name =>  $permission)
                            <?php check_permission_exist($permission,$role->Permissions->pluck('route_name')->toArray()) ?>
                            <!--from-input-col-open-->
                            <div class="col-md-2 col-sm-3 from-input-col">
                                <div class="form-group">
                                    <input type="checkbox" name="permissions[]"  value="{{ $permission }}" @if(check_permission_exist($permission,$role->Permissions->pluck('route_name')->toArray())) checked @endif />
                                    <label class="control-label permission">{{$name}}</label>
                                </div>
                            </div>
                            <!--from-input-col-close-->
                            @endforeach

                        </div>
                        <!--from-input-wraper-close-->
                        @endforeach


                        <!--from-input-wraper-open-->
                        <div class="row from-input-wraper">
                            <!--from-input-col-open-->
                            <div class="col-sm-12 text-right from-input-col mt-27">
                                <div class="form-group">
                                    <button type="submit" class="btn orange" id="save"> Update </button>
                                    <a href="{{route('role.index')}}"  class="btn orange" id="cancel"> Cancel </a>
                                </div>
                            </div>
                            <!--from-input-col-close-->
                        </div>
                        <!--from-input-wraper-close-->

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
    <script>
        jQuery(document).ready(function () {
            // initiate layout and plugins
            App.init();
            Admin.init();
            $('#cancel').click(function () {
                window.location.href = "{!! URL::route('role.index') !!}";
            });
        });
    </script>
@stop
