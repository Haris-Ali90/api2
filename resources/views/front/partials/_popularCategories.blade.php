<section class="popular-categories">
    <div class="container">
        <div class="row">
            <div class="col-12 col-lg-11 mx-auto">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-2">
                        <h4 class="section-heading"><span class="d-block">Browse popular</span>categories</h4>
                    </div>
                    <div class="col-12 col-lg-10">
                        <ul>
                            @if($featuredCategories)
                                @foreach($featuredCategories as $category)
                                <li>
                                    <a href="{!! route('category.details', $category->slug) !!}" class="category-wrap">
                                        <img src="{!! asset(uploadsDir().$category->image_small) !!}" width="52" height="52" class="img-fluid" 
                                        alt="{!! $category->slug !!}">
                                        <p class="cat-title" style="color: {!! $category->color !!}">{!! $category->name !!}</p>
                                    </a>
                                </li>
                                @endforeach
                                <li>
                                    <a href="{!! route('category') !!}" class="category-wrap">
                                        <img src="{!! asset('assets/front/images/categories/view-all.png') !!}" width="52" height="52" class="img-fluid" alt="All Category">
                                        <p class="cat-title black">View All</p>
                                    </a>
                                </li>
                            @endif
                            
                            {{--<li>
                                <a href="javascript:void(0);" class="category-wrap">
                                    <img src="{!! asset('assets/front/images/categories/gifts.png') !!}" class="img-fluid" alt="Category Image">
                                    <p class="cat-title pink">Gifts</p>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="category-wrap">
                                    <img src="{!! asset('assets/front/images/categories/health.png') !!}" class="img-fluid" alt="Category Image">
                                    <p class="cat-title blue">Health</p>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="category-wrap">
                                    <img src="{!! asset('assets/front/images/categories/home.png') !!}" class="img-fluid" alt="Category Image">
                                    <p class="cat-title firozi">Home</p>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="category-wrap">
                                    <img src="{!! asset('assets/front/images/categories/electronics.png') !!}" class="img-fluid" alt="Category Image">
                                    <p class="cat-title yellow">Electronics</p>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="category-wrap">
                                    <img src="{!! asset('assets/front/images/categories/travel.png') !!}" class="img-fluid" alt="Category Image">
                                    <p class="cat-title red">Travel</p>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="category-wrap">
                                    <img src="{!! asset('assets/front/images/categories/automotive.png') !!}" class="img-fluid" alt="Category Image">
                                    <p class="cat-title blue">Automotive</p>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="category-wrap">
                                    <img src="{!! asset('assets/front/images/categories/entertainment.png') !!}" class="img-fluid" alt="Category Image">
                                    <p class="cat-title green">Entertainment</p>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="category-wrap">
                                    <img src="{!! asset('assets/front/images/categories/view-all.png') !!}" class="img-fluid" alt="Category Image">
                                    <p class="cat-title black">View All</p>
                                </a>
                            </li>--}}
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>