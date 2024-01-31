@extends('front.' . $template . '.layouts.app')

@section('head')
<title>500 - Internal Server Error</title>
@endSection

@section('content')
	<!--============================
		About Page Header
	=============================-->
	<section class="page-header" style="background-size:cover; background-repeat:no-repeat; background-image:url();">
		<div class="container">
			<div class="row">
				<div class="col-sm-12">
					<h1>500 - Internal Server Error</h1>
					<ul class="list-unstyled">
						<li><a href="{!! route('index') !!}">Home</a></li>
						<li class="active">500 - Internal Server Error</li>
					</ul>
				</div>
			</div>
		</div>
	</section><!-- Ends: .page-header -->
	
	<!--============================
		About Page Content
	=============================-->
	<section class="about-ds">
		<div class="container">
			<div class="row">
	            <div class="col-md-12 col-sm-12 about-ds-content">
					<div class="section-header03">
						<h2>500 - Internal Server Error</h2>
					</div>
				</div>
	            <div class="col-md-12 col-sm-12 about-ds-content">
                    <h3>Something went wrong :(</h3>

                    <p>
                    	Looks like we're having some server issues.<br>
                    	Let's explore other pages of the website <a href="{!! URL::to('/') !!}">here</a>.
                    </p>
            	</div>
			</div>
		</div>
	</section><!-- Ends: .about-ds -->
@endSection