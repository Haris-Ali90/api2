<div class="row">
    @if(can_view_cards('customer_card_count',$dashbord_cards_rights))
        <div class="col-lg-4 col-md-4 col-sm-4">
            <div class="dashboard-stat dashboard-cart-box-one green">
                <div class="visual" style="right:10px;">
                    <i class="fa fa-users" ></i>
                </div>
                <div class="details">
                    <div class="number">
                        {{$subAdmin}}
                    </div>
                    <div class="desc">
                       Customer
                    </div>
                </div>
                <a class="more" href="{{route('customer.index')}}">
                    See Customer List <i class="m-icon-swapright m-icon-white"></i>
                </a>
            </div>
        </div>
    @endif
    @if(can_view_cards('banner_card_count',$dashbord_cards_rights))
        <div class="col-lg-4 col-md-4 col-sm-4">
            <div class="dashboard-stat dashboard-cart-box-one green">
                <div class="visual" style="right:10px;">
                    <i class="fa fa-users" ></i>
                </div>
                <div class="details">
                    <div class="number">
                        {{$banner}}
                    </div>
                    <div class="desc">
                        New Banner
                    </div>
                </div>
                <a class="more" href="{{route('banner.index')}}">
                    See Banner List <i class="m-icon-swapright m-icon-white"></i>
                </a>
            </div>
        </div>
    @endif
</div>
<div class="clearfix"></div>
<!-- Dashboard cards close -->
