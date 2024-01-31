<div class="fix-ads">
	@if(isset($gSideBanners) && count($gSideBanners) > 0)
	    @if(isset($gSideBanners[0]))
	    <div class="left">
	        <a href="{!! $gSideBanners[0]->click_url !!}">
	        	<img src="{!! asset(uploadsDir().$gSideBanners[0]->image) !!}" width="120" height="600" 
	        	alt="{!!$gSideBanners[0]->alt_text!!}">
	        </a>
	    </div>
	    @endif
	    @if(isset($gSideBanners[1]))
	    <div class="right">
	        <a href="{!! $gSideBanners[1]->click_url !!}">
	        	<img src="{!! asset(uploadsDir().$gSideBanners[1]->image) !!}" width="120" height="600" 
	        	alt="{!! $gSideBanners[1]->alt_text !!}">
	        </a>
	    </div>
	    @endif
    @endif
</div>