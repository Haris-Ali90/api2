@section('head')

   {{-- <title>{!! (isset($metaData['title']) && !empty($metaData['title'])) ? $metaData['title'] : $siteSettings->site_title !!}</title>
    <meta name="description" content="{!! (isset($metaData['description']) && !empty($metaData['description'])) ? $metaData['description'] : '' !!}">
    <meta name="keywords" content="{!! (isset($metaData['keywords']) && !empty($metaData['keywords'])) ? $metaData['keywords'] : '' !!}">

    <meta property=og:title content="{!! (isset($metaData['title']) && !empty($metaData['title'])) ? $metaData['title'] : $siteSettings->site_title !!}"/>
    <meta property=og:site_name content="{!! $siteSettings->site_title !!}"/>
    <meta property=og:url content="{!! (isset($metaData['url']) && !empty($metaData['url'])) ? $metaData['url'] : '' !!}"/>
    <meta property=og:image content="{!! (isset($metaData['image']) && !empty($metaData['image'])) ? $metaData['image'] : uploadsUrl($siteSettings->logo) !!}"/>
    <meta property=og:description content="{!! (isset($metaData['description']) && !empty($metaData['description'])) ? $metaData['description'] : '' !!}"/>

    <link rel="canonical" href="{!! $siteSettings->website !!}" />--}}

@endsection
