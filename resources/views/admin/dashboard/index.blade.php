@extends('admin.layouts.app')

@section('content')
<!-- BEGIN PAGE HEADER-->
@include('admin.partials.errors')
<div class="row">
    <div class="col-md-12">
        <!-- BEGIN PAGE TITLE & BREADCRUMB-->
        <h3 class="page-title">{{ $pageTitle }} <small></small></h3>
        {{ Breadcrumbs::render('dashboard.index') }}
        <!-- END PAGE TITLE & BREADCRUMB-->
    </div>
</div>

@if (Session::get('success'))
<div class="alert alert-danger">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
    {!! Session::get('success') !!}
</div>
@endif
<!-- END PAGE HEADER-->

<!-- BEGIN DASHBOARD STATS -->

@if($dashbord_cards_rights)
    @include('admin.partials.dashboard-cards')
@endif
@stop

@section('footer-js')
<!-- BEGIN PAGE LEVEL SCRIPTS -->
<script src="{{ asset('assets/admin/scripts/core/app.js') }}" type="text/javascript"></script>
<!-- END PAGE LEVEL SCRIPTS -->
<script>
jQuery(document).ready(function() {
   App.init(); // initlayout and core plugins
   Admin.init();
});
</script>
@stop
