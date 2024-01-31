@if (count($breadcrumbs))

    <ul class="page-breadcrumb breadcrumb">
        @foreach ($breadcrumbs as $breadcrumb)

            @if ($breadcrumb->url && !$loop->last)
                <li>
                    @if ($loop->first)
                        <i class="fa fa-home"></i>
                    @endif
                    <a href="{{ $breadcrumb->url }}">{!! $breadcrumb->title !!}</a>
                    <i class="fa fa-angle-right"></i>
                </li>
            @else
                <li class="active">
                    @if ($loop->first)
                        <i class="fa fa-home"></i>
                    @endif
                    {!! $breadcrumb->title !!}
                </li>
            @endif

        @endforeach
    </ul>

@endif