<div class="col-md-8 col-md-offset-2">
    <p><small><em>Right click on any image above, and choose copy image. Then paste in the text editor below.</em></small></p>
</div>

<div class="col-md-8 col-md-offset-2" style="overflow-y:scroll; overflow-x:hidden; height:100px; margin-top:20px;">
    @if (isset($mediaFile) && count($mediaFile) > 0)
	    <ol>
	        @foreach ($mediaFile as $media)
	            @if (getFileExtension($media->filename) == 'pdf')
	                <li><a href="{!! asset(uploadsDir().$media->filename) !!}" target="_blank">{!! $media->caption !!}</a></li>
	            @endif
	        @endforeach
	    </ol>
    @endif
</div>

<div class="col-md-8 col-md-offset-2">
    <p><small><em>Right click on any PDF link above, and choose copy link address. Then paste in the text editor > Link icon below.</em></small></p>
</div>