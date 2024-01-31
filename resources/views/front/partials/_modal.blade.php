    @if(isset($offerDetail['offer_type']) && strtolower($offerDetail['offer_type']) == 'codes')
    <div class="modal fade vProModal show-code" id="codeModal" tabindex="-1" role="dialog" aria-labelledby="codeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <img src="{!! asset('assets/front/images/close-button.png') !!}" alt="Close Button">
                    </button>
                    <div class="store-image">
                        <img src="{!! asset('uploads/'. $offerDetail['logo']) !!}" alt="">
                    </div>
                    <h6 class="about-deal">{!! $offerDetail['title'] !!}</h6>
                    <p class="instructions">Copy the code, paste it into the checkout box, and you will be suprised</p>
                    <div class="code-wrap">
                        <p class="code">{!! $offerDetail['voucher_code'] !!}</p>
                        <button data-code="{!! $offerDetail['voucher_code'] !!}" data-toggle="popover" data-content="Text Copied" class="copy-button">Copy Code</button>
                    </div>
                    <div class="review">
                        <span class="text">Did it work?</span>
                        <span class="emoticon happy">
                            <input type="radio" id="yes" name="didItWork">
                            <label for="yes"></label>
                            <span class="emoticon-text">Yes</span>
                        </span>
                        <span class="emoticon sad">
                            <input type="radio" id="no" name="didItWork">
                            <label for="no"></label>
                            <span class="emoticon-text">No</span>
                        </span>
                    </div>
                    <div class="meta">
                        <div class="date"><i class="fa fa-clock-o"></i> Expiration date: <span class="expire_date">{{ offerDateFormat($offerDetail['expiry_date']) }}</span></div>
                        <ul class="share">
                            <li><a href="javascript:void(0);" class="fb" tabindex="0"><i class="fab fa-facebook-f"></i></a></li>
                            <li><a href="javascript:void(0);" class="tw" tabindex="0"><i class="fab fa-twitter"></i></a></li>
                            <li><a href="javascript:void(0);" class="wa" tabindex="0"><i class="fab fa-whatsapp"></i></a></li>
                            <li><a href="javascript:void(0);" class="ib" tabindex="0"><i class="fas fa-inbox"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    @if(isset($offerDetail['offer_type']) && strtolower($offerDetail['offer_type']) == 'deals')

    @php
        $clickUrl = (!empty($offer['affiliate_url'])) ? $offer['affiliate_url'] : $offer['store_affiliate_url'];
    @endphp

    <div class="modal fade vProModal show-deal" id="dealModal" tabindex="-1" role="dialog" aria-labelledby="dealModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <img src="{!! asset('assets/front/images/close-button.png') !!}" alt="Close Button">
                    </button>
                    <div class="store-image">
                        <img src="{!! asset('uploads/'. $offerDetail['logo']) !!}" alt="">
                    </div>
                    <h6 class="about-deal">{!! $offerDetail['title'] !!}</h6>
                    <p class="return-underline text-purple text-center mb-50">Go to <a href="{!! $clickUrl !!}" class="text-yellow store-name">{!! $offerDetail['store_name'] !!}</a></p>
                    
                    <div class="meta border-top pt-30">
                        <div class="date"><i class="fa fa-clock-o"></i> Expiration date: <span class="expire_date">{{ offerDateFormat($offerDetail['expiry_date']) }}</span></div>
                        <ul class="share">
                            <li><a href="javascript:void(0);" class="fb" tabindex="0"><i class="fab fa-facebook-f"></i></a></li>
                            <li><a href="javascript:void(0);" class="tw" tabindex="0"><i class="fab fa-twitter"></i></a></li>
                            <li><a href="javascript:void(0);" class="wa" tabindex="0"><i class="fab fa-whatsapp"></i></a></li>
                            <li><a href="javascript:void(0);" class="ib" tabindex="0"><i class="fas fa-inbox"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif