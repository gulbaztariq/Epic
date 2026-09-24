{{-- The XML declaration is prepended by PageController::sitemapXml(). It must
     not appear here: Blade tokenises this file as PHP, so where
     short_open_tag is on its opening bracket would end Blade compilation. --}}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        @isset($url['lastmod'])<lastmod>{{ $url['lastmod']->toAtomString() }}</lastmod>@endisset
        <priority>{{ $url['priority'] ?? '0.5' }}</priority>
    </url>
@endforeach
</urlset>
