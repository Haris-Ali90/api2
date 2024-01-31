@extends('front.layouts.app')

@section('head')
<title>Voucher Pro | 404 Page</title>
@endSection

@section('css')
	<!-- <link rel="stylesheet" href="{!! asset('assets/front/css/404.css') !!}"> -->
@endsection

@section('content')
@include('front.partials._headerSearch')
	<section class="not-found-page">
        <div class="container">
            @include('front.partials._sideBanners')
            <img src="{!! asset('assets/front/images/404.svg') !!}" class="not-found-svg" alt="404">
            <p class="return-home text-purple">Return to the <a href="{!! route('index') !!}" class="text-yellow">homepage</a></p>
        </div>
    </section>
    @include('front.partials._popularCategories')
@endSection
