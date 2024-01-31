<section class="inner-search">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <!-- <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Categories</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Entertainment</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Rakuten TV</li>
                    </ol>
                </nav> -->
                @if(isset($breadcrumbs))
                    {{ Breadcrumbs::view('front/partials/breadcrumbs',$breadcrumbs->name, $breadcrumbs) }}
                @endif
            </div>
            <div class="col-12 col-md-6">
                <!-- <form class="search-form" method="get" action="{!! route('search.stores.submit') !!}">
                    <input type="text" id="searchBox" placeholder="Search here" name="q" value="">
                    <button type="submit">Search</button>
                </form> -->

                <div class="vProSearch">
                    <form class="search-form" id="searchFieldId" method="get" action="{!! route('search.stores.submit') !!}">
                        <input type="text" id="searchBox" placeholder="Search here" class="search-input" name="q" value="" autocomplete="off">
                        <button class="search-submit" id="searchBtn">Search</button>
                        <div class="suggestions-wrap">
                            @if(isset($gFeaturedStores) && count($gFeaturedStores) > 0)
                            <span class="PopularBrands">
                            <h6>Popular Brands</h6>
                            <ul class="suggestions" id="PopularBrands">
                                @foreach($gFeaturedStores as $row)
                                    <li class="data data-1"><a href="{!! route('page', $row->website) !!}"><img src="{!! uploadsUrl($row->logo)!!}" alt="{{ $row->name }}">{{ $row->name }}</a></li>
                                @endforeach
                            </ul>
                            </span>
                            @endif
                            <h6>Stores</h6>
                            <ul class="suggestions suggestionsList store" id="store">
                                <!-- <li class="data data-1"><a href="javascript:void(0);"><img src="{!! asset('assets/front/images/featured-vouchers/1.jpg') !!}" alt="Brand Logo">SammyDress</a></li> -->
                            </ul>
                            <h6 class="mt-20">Categories</h6>
                            <ul class="suggestions suggestionsList category" id="category">
                                <!-- <li class="data data-6"><a href="javascript:void(0);"><img src="{!! asset('assets/front/images/categories/health.png') !!}" class="img-fluid" alt="Category Image"> Health & Beauty</a></li> -->
                            </ul>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</section>