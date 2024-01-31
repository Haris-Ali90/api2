<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    @yield('head')
    
    {!! $siteSettings->head_area !!}

	<!-- Favicon -->
	<link rel="icon" href="{!! asset('assets/front/images/icon/favicon-'. config('app.site') .'.png') !!}" type="image/x-icon" />
	
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800|Roboto+Slab:300,400,700">

	<link rel="stylesheet" href="{!! asset('assets/front/css/main-'. config('app.site') .'.css') !!}">

    @yield('css')

</head>

<body>

	<!--==================
		Preloader
	===================-->

	<div class="preloader">
        <div class="outer-circle">
            <div class="inner-circle"></div>
        </div>
    </div>

    <div class="main-wrapper">
	<!--==================
		Header
	===================-->

	<header>
	    <div class="container">
	        <nav class="navbar navbar-expand-lg px-0">
	        	@if ($siteSettings->logo != '' && file_exists(uploadsDir() . $siteSettings->logo))
	            	<a class="navbar-brand" href="{!! route('index') !!}">
	            		<img src="{!! uploadsUrl($siteSettings->logo) !!}" width="250" height="35" alt="{{ $siteSettings->site_title }}">
	            	</a>
	            @endif
	            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#voucherProNav" aria-controls="voucherProNav" aria-expanded="false" aria-label="Toggle navigation">
	                <span class="navbar-toggler-icon"></span>
	            </button>
	            
	            <div class="collapse navbar-collapse" id="voucherProNav">
	                <ul class="navbar-nav ml-auto">
	                	@if($menus > 0)	
	                	@foreach ($menus as $menu)
							@if ($menu->parent_id == 0)
								@if(request()->is('/') && $menu->slug == 'home')
									@php $activeClass = 'active'; @endphp
								@elseif(request()->is($menu->slug))
									@php $activeClass = 'active'; @endphp
								@else
									@php $activeClass = ''; @endphp
								@endif
								@if($menu->slug != 'home')
									<li class="nav-item">
				                        <a class="nav-link {!! $activeClass !!}" href="{!! route('page', ($menu->slug != 'home' ? $menu->slug : '')) !!}">{!! $menu->name !!}</a>
				                    </li>
			                    @endif
							@endif
						@endforeach
	                    @endif
	                    <!-- <li class="nav-item">
	                        <a class="nav-link" href="{!! route('page', 'daily-treadning') !!}">Daily Trendings</a>
	                    </li>
	                    <li class="nav-item">
	                        <a class="nav-link" href="{!! route('page', 'daily-treadning') !!}">Travel Deals</a>
	                    </li>
	                    <li class="nav-item">
	                        <a class="nav-link" href="{!! route('category') !!}">Categories</a>
	                    </li>
	                    <li class="nav-item">
	                        <a class="nav-link" href="{!! route('store') !!}">All Stores</a>
	                    </li>
	                    <li class="nav-item">
	                        <a class="nav-link" href="{!! route('contact') !!}">Contact Us</a>
	                    </li> -->
	                    <li class="nav-item associate">
	                    	@if ($siteSettings->after_menu_logo != '' && file_exists(uploadsDir() . $siteSettings->after_menu_logo))
				            <a class="nav-link" href="{!! route('page', $siteSettings->after_menu_slug) !!}"><img src="{!! uploadsUrl($siteSettings->after_menu_logo) !!}" alt="{!! $siteSettings->after_menu_slug !!}"></a>
				            @endif
	                    </li>
	                </ul>
	            </div>
	        </nav>
	    </div>
	</header>

	@yield('content')

	<!--==================
			Footer
	===================-->

	<section class="newsletter">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="newsletter-container">
                    <div class="row">
                        <div class="col-12 col-sm-10 mx-sm-auto">
                            <div class="newsletter-wrap">
                                <img src="{!! asset('assets/front/images/newsletter-envelope.png') !!}" width="234" height="155" class="img-fluid" alt="Newsletter" />
                                <div class="form-area">
                                    <h5>Get voucher codes and online deals delivered straight to your inbox</h5>
                                    <form id="newsletterForm" action="{{ route('newsletter.subscribe') }}">
                                        <input type="email" class="input-email" name="email" placeholder="Please write your Email to subscribe">
                                        <button type="submit" class="send-newsletter"><i class="far fa-envelope"></i></button>
                                        <label class="disclaimer" for="newsletter-disclaimer">
                                            <input type="checkbox" id="newsletter-disclaimer" name="disclaimer">
                                            <span class="box"></span>
                                            <span class="text">Please click here if you would like to receive information about our services, products and exclusive offers through email. You can <a href="{{ route('newsletter.unsubscribe') }}" class="text-blue">Unsubscribe</a> at any time. Read our <a href="{!! route('page', 'privacy-policy') !!}" target="_blank" class="text-purple">Privacy Policy</a></span>
                                        </label>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
	</section>

	<div class="alert alert-success" role="alert">
	    <p class="text-success"></p>
	</div>
	<div class="alert alert-danger" role="alert">
	    <p class="text-danger"></p>
	</div>

	<input type="hidden" id="searchUrl" value="{!! route('search.stores') !!}">

	<footer>
	    <div class="container">
	        <div class="row">
	            <div class="col-12">
	                <div class="footer-top">
	                    <div class="row text-center text-sm-left">
	                        <div class="col-12 col-sm-6 col-lg-3">
	                            <h6 class="footer-heading">Browse Site</h6>
	                            <ul class="footer-links">
	                                <li><a href="{!! route('store') !!}">All Stores</a></li>
	                                <li><a href="{!! route('category') !!}">Top Categories</a></li>
	                                <li><a href="/blog/">Blog</a></li>
	                                <li><a href="{!! route('contact') !!}">Contact Us</a></li>
	                                <li><a href="{!! route('voucher.request') !!}">Submit A Voucher</a></li>
	                                <li><a href="{!! route('store') !!}">Sitemap</a></li>
	                            </ul>
	                            
	                            <!-- <h6 class="footer-heading">Download our Mobile App</h6>
	                            <ul class="list-inline app-links">
	                                <li class="list-inline-item">
	                                    <a href="javascript:void(0);"><span class="app google-play"></span></a>
	                                </li>
	                                <li class="list-inline-item">
	                                    <a href="javascript:void(0);"><span class="app app-store"></span></a>
	                                </li>
	                            </ul>
	                            <div>
	                                <a href="amazon-associates.php">
	                                    <amp-img src="{!! asset('assets/front/images/amazon-big.png') !!}" class="img-fluid" alt="Amazon Associate"></amp-img>
	                                </a>
	                            </div> -->
	                        </div>
	                        
	                        <div class="col-12 col-sm-6 col-lg-3">
	                            <h6 class="footer-heading">Special Events</h6>
	                            <ul class="footer-links">
	                            	@if(isset($gEvents) && count($gEvents) > 0)
	                            	@foreach($gEvents as $row)
	                                <li><a href="{!! route('page', $row->slug) !!}">{{ $row->name }}</a></li>
	                                @endforeach
	                                @endif
	                            </ul>
	                        </div>
	                        
	                        <div class="col-12 col-sm-6 col-lg-2">
	                            <h6 class="footer-heading">Information</h6>
	                            <ul class="footer-links">
	                                <li><a href="{!! route('page', 'about-us') !!}">About Us</a></li>
	                                <li><a href="{!! route('page', 'cookies-policy') !!}">Cookies Policy</a></li>
	                                <li><a href="{!! route('page', 'privacy-policy') !!}">Privacy Policy</a></li>
	                                <li><a href="{!! route('page', 'terms-conditions') !!}">Terms & Conditions</a></li>
	                            </ul>
	                            <h6 class="footer-heading">Popular Stores</h6>
	                            <ul class="footer-links">
	                            	@if(isset($gFeaturedStores) && count($gFeaturedStores) > 0)
	                            	@foreach($gFeaturedStores as $row)
	                                	<li><a href="{!! route('page', $row->website) !!}">{{ $row->name }}</a></li>
	                                @endforeach
	                                @endif
	                            </ul>
	                        </div>

	                        <div class="col-12 col-sm-6 col-lg-4">
	                            <h6 class="footer-heading">Write to Us</h6>
	                            <p class="footer-text">We welcome all comments and feedback on our vouchers, discount codes and hot deals portal</p>
	                            <form method="post" id="footerContactForm" action="{{ route('process-contact') }}" onsubmit="return false">
	                            	<div class="row">
	                                    <div class="col-12 footer-form-fields">
	                                        <input class="form-control" name="name" value="{!! old('name') !!}" type="text" placeholder="Your name" maxlength="128" required>
	                                    </div>
	                                    <div class="col-12 footer-form-fields">
	                                        <input class="form-control" name="email" value="{!! old('email') !!}" type="email" placeholder="Email" maxlength="128" required>
	                                    </div>
	                                    <div class="col-12 footer-form-fields">
	                                        <textarea class="form-control" placeholder="Message" name="description" maxlength="65000" required>{!! old('description') !!}</textarea>
	                                    </div>
	                                    <div class="col-12 footer-form-fields">
	                                        <label class="disclaimer" for="footer-disclaimer">
	                                            <input type="checkbox" name="terms" id="footer-disclaimer">
	                                            <span class="box"></span>
	                                            <span class="text">I have read and agree to the <a href="{!! route('page', 'terms-conditions') !!}" target="_blank">Terms and Conditions</a> and <a href="{!! route('page', 'privacy-policy') !!}" target="_blank">Privacy Policy</a></span>
	                                        </label>
	                                    </div>
	                                    <div class="col-12 footer-form-fields">
	                                        <button type="submit">Submit</button>
	                                    </div>
	                                </div>
	                            </form>

	                            <p class="text-white">Socialise with us!</p>
	                            <ul class="list-social list-inline">
	                            	@if (!empty($siteSettings->facebook))
	                                	<li class="list-inline-item"><a href="{!! $siteSettings->facebook !!}" class="fb" target="_blank"><i class="fab fa-facebook-f" aria-hidden="true"></i></a></li>
                                	@endif
                                	@if (!empty($siteSettings->twitter))
	                                	<li class="list-inline-item"><a href="{!! $siteSettings->twitter !!}" class="tw" target="_blank"><i class="fab fa-twitter" aria-hidden="true"></i></a></li>
                                	@endif
                                	@if (!empty($siteSettings->googleplus))
	                                	<li class="list-inline-item"><a href="{!! $siteSettings->googleplus !!}" class="go" target="_blank"><i class="fab fa-google" aria-hidden="true"></i></a></li>
	                                @endif
	                                @if (!empty($siteSettings->pinterest))
	                                	<li class="list-inline-item"><a href="{!! $siteSettings->pinterest !!}" class="pi" target="_blank"><i class="fab fa-pinterest-p" aria-hidden="true"></i></a></li>
	                                @endif
	                            </ul>
	                        </div>
	                    </div>
	                </div>
	                <div class="footer-bottom">
	                    <p class="copyright">
	                    	&copy;@php echo date('Y') @endphp
	                    	{!! $siteSettings->copyright !!} 
	                    	{!! $siteSettings->footer_sentence !!}
	                    </p>
	                </div>
	            </div>
	        </div>
	    </div>
	</footer>

</div>

	<!--==================
			JS Files
	===================-->


	<script src="{!! asset('assets/front/js/all.min.js') !!}"></script>
	<script src="{!! asset('assets/front/js/script.js') !!}"></script>

	@yield('js')	
	{!! $siteSettings->footer_scripts !!}

</body>
</html>