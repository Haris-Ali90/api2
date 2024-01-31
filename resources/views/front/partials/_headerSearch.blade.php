<!-- <section class="search-area">
    <div class="vProSearch">
        <h1>Exclusive VoucherPro Coupons</h1>
        <p>Lorem ipsum is a dummy text</p>
        <form class="search-form" id="searchFieldId" method="get" action="{!! route('search.stores.submit') !!}">
            <input type="text" id="searchBox" name="q" placeholder="Search here" class="search-input" autocomplete="off">
            <button class="search-submit" id="searchBtn">Search</button>
            <span class="search-close">&#10005;</span>
            <div class="suggestions-wrap">
                <ul class="suggestions">
                    <li></li>
                </ul>
            </div>
        </form>
    </div>
</section> -->

<section class="search-area">
    <div class="vProSearch">
        <h1>Exclusive {!! ($siteSettings->site_title) ? $siteSettings->site_title : '' !!} Coupons</h1>
        <p>Explore Over a Million Products</p>
        <form class="search-form" id="searchFieldId" method="get" action="{!! route('search.stores.submit') !!}">
            <input type="text" id="searchBox" name="q" placeholder="Search here" class="search-input" autocomplete="off">
            <button class="search-submit" id="searchBtn">Search</button>
            <span class="search-close">&#10005;</span>

            <div class="suggestions-wrap">
                <h6 class="storeLabel">Store</h6>
                <ul class="suggestions suggestionsList store" id="store">
                    <!-- <li><a href="javascript:void(0);"><img src="{!! asset('assets/front/images/featured-vouchers/1.jpg') !!}" alt="Brand Logo">Sabon</a></li> -->
                </ul>
                <h6 class="mt-20 categoryLabel">Categories</h6>
                <ul class="suggestions suggestionsList category" id="category">
                    <!-- <li><a href="javascript:void(0);"><img src="{!! asset('assets/front/images/categories/health.png') !!}" class="img-fluid" alt="Category Image"> Health & Beauty</a></li> -->
                </ul>
            </div>
        </form>
    </div>
</section>