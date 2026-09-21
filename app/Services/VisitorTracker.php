<?php

namespace App\Services;

use App\Models\IpLocation;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records one row per page view of the public website.
 *
 * Runs after the response has been sent (see TrackVisitors middleware), so it
 * never slows a page down. Location is filled in from the cached IP lookup when
 * it is already known, and resolved later by epic:resolve-visitor-locations.
 */
class VisitorTracker
{
    /** User agents we count as crawlers, monitors and tooling rather than people. */
    private const BOT_PATTERN = '~(bot|crawl|spider|slurp|mediapartners|facebookexternalhit|'
        .'whatsapp|telegrambot|discordbot|slackbot|linkedinbot|twitterbot|embedly|'
        .'headlesschrome|phantomjs|puppeteer|playwright|lighthouse|pagespeed|gtmetrix|'
        .'pingdom|uptimerobot|statuscake|newrelic|datadog|semrush|ahrefs|mj12|dotbot|'
        .'petalbot|yandex|baidu|bingpreview|curl/|wget/|python-requests|python-urllib|'
        .'go-http-client|okhttp|java/|libwww|httpclient|scrapy|apachebench|siege|'
        .'masscan|zgrab|nmap)~i';

    /** Paths that are never counted as page views. */
    private const IGNORED_PATHS = [
        'admin', 'admin/*',
        'up',
        'sitemap.xml', 'robots.txt', 'favicon.ico',
        'uploads/*', 'css/*', 'js/*', 'images/*', 'build/*',
    ];

    public function shouldTrack(Request $request, Response $response): bool
    {
        if (setting('analytics_enabled', '1') !== '1') {
            return false;
        }

        if (! $request->isMethod('GET') || $request->ajax()) {
            return false;
        }

        if ($request->is(...self::IGNORED_PATHS)) {
            return false;
        }

        // Only real page views: rendered HTML, not redirects, files or errors
        // other than a genuine "page not found".
        if (! in_array($response->getStatusCode(), [200, 404], true)) {
            return false;
        }

        if (! Str::contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return false;
        }

        if (setting('analytics_respect_dnt', '0') === '1'
            && ($request->headers->get('DNT') === '1' || $request->headers->get('Sec-GPC') === '1')) {
            return false;
        }

        return true;
    }

    public function track(Request $request, Response $response): ?Visit
    {
        try {
            $agent = (string) $request->userAgent();
            $isBot = $this->isBot($agent);

            if ($isBot && setting('analytics_track_bots', '1') !== '1') {
                return null;
            }

            $ip = (string) $request->ip();
            $ipHash = $this->hash($ip);
            $device = $this->deviceInfo($agent, $isBot);

            $attributes = [
                'visitor_key' => $this->hash($ip.'|'.$agent),
                'session_key' => $this->hash('session|'.$request->session()->getId()),
                'path' => Str::limit('/'.ltrim($request->path(), '/'), 185, ''),
                'page_title' => $this->pageTitle($response),
                'referrer' => $this->referrer($request),
                'referrer_host' => $this->referrerHost($request),
                'ip_address' => $this->storableIp($ip),
                'ip_hash' => $ipHash,
                'device_type' => $device['device_type'],
                'browser' => $device['browser'],
                'platform' => $device['platform'],
                'language' => $this->language($request),
                'is_bot' => $isBot,
                'visited_at' => now(),
                'location_resolved' => false,
            ];

            $attributes['is_new_visitor'] = ! Visit::where('visitor_key', $attributes['visitor_key'])->exists();

            $attributes = array_merge($attributes, $this->knownLocation($request, $ip, $ipHash));

            return Visit::create($attributes);
        } catch (\Throwable $e) {
            // Analytics must never break a page.
            Log::warning('Visitor tracking failed: '.$e->getMessage());

            return null;
        }
    }

    /* --------------------------------------------------------- Location */

    /**
     * Location we can fill in immediately: a previously resolved lookup, or the
     * country header a CDN such as Cloudflare adds to the request.
     */
    protected function knownLocation(Request $request, string $ip, string $ipHash): array
    {
        $cached = IpLocation::where('ip_hash', $ipHash)->first();

        if ($cached?->isResolved()) {
            return $cached->visitAttributes();
        }

        if (setting('analytics_geolocation', '1') === '1' && ! $cached) {
            IpLocation::create([
                'ip_hash' => $ipHash,
                'ip_address' => $this->isPublicIp($ip) ? $ip : null,
            ]);
        }

        $cdnCountry = $request->headers->get('CF-IPCountry')
            ?: $request->headers->get('X-Vercel-IP-Country')
            ?: $request->headers->get('X-Geo-Country');

        if ($cdnCountry && strlen($cdnCountry) === 2 && $cdnCountry !== 'XX') {
            $code = strtoupper($cdnCountry);

            return [
                'country_code' => $code,
                'country' => GeoLocator::countryName($code),
            ];
        }

        return [];
    }

    protected function isPublicIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * Visitor addresses are stored with the host part removed unless the site
     * owner has chosen to keep full addresses.
     */
    protected function storableIp(string $ip): ?string
    {
        if ($ip === '') {
            return null;
        }

        if (setting('analytics_store_full_ip', '0') === '1') {
            return $ip;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';

            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return implode(':', array_slice(explode(':', $ip), 0, 4)).'::';
        }

        return null;
    }

    /* ------------------------------------------------------- Request bits */

    protected function hash(string $value): string
    {
        return hash('sha256', $value.'|'.config('app.key'));
    }

    protected function referrer(Request $request): ?string
    {
        $referrer = $request->headers->get('referer');

        return filled($referrer) ? Str::limit($referrer, 500, '') : null;
    }

    /** External referrers only — internal navigation is not a traffic source. */
    protected function referrerHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        if (! $host || Str::lower($host) === Str::lower($request->getHost())) {
            return null;
        }

        return Str::limit(Str::lower(Str::after($host, 'www.')), 185, '');
    }

    protected function language(Request $request): ?string
    {
        $language = $request->headers->get('Accept-Language');

        if (blank($language)) {
            return null;
        }

        return Str::limit(trim(Str::before($language, ',')), 11, '');
    }

    /** Pull the <title> out of the rendered page so reports read naturally. */
    protected function pageTitle(Response $response): ?string
    {
        $html = Str::limit((string) $response->getContent(), 4000, '');

        if (preg_match('~<title[^>]*>(.*?)</title>~is', $html, $matches)) {
            $title = trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            return $title === '' ? null : Str::limit($title, 250);
        }

        return null;
    }

    /* ---------------------------------------------------- Agent parsing */

    public function isBot(string $agent): bool
    {
        return $agent === '' || (bool) preg_match(self::BOT_PATTERN, $agent);
    }

    /**
     * @return array{device_type: string, browser: ?string, platform: ?string}
     */
    public function deviceInfo(string $agent, ?bool $isBot = null): array
    {
        $isBot ??= $this->isBot($agent);

        return [
            'device_type' => $isBot ? 'bot' : $this->deviceType($agent),
            'browser' => $this->browser($agent),
            'platform' => $this->platform($agent),
        ];
    }

    protected function deviceType(string $agent): string
    {
        if (preg_match('~ipad|tablet|kindle|silk|playbook|(android(?!.*mobile))~i', $agent)) {
            return 'tablet';
        }

        if (preg_match('~mobile|iphone|ipod|android|blackberry|opera mini|opera mobi|iemobile|windows phone~i', $agent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    protected function browser(string $agent): ?string
    {
        $browsers = [
            'Edge' => '~edg(e|a|ios)?/~i',
            'Opera' => '~(opr/|opera)~i',
            'Samsung Internet' => '~samsungbrowser~i',
            'Vivaldi' => '~vivaldi~i',
            'Brave' => '~brave~i',
            'Chrome' => '~(chrome|crios|chromium)~i',
            'Firefox' => '~(firefox|fxios)~i',
            'Safari' => '~safari~i',
            'Internet Explorer' => '~(msie|trident)~i',
        ];

        foreach ($browsers as $name => $pattern) {
            if (preg_match($pattern, $agent)) {
                return $name;
            }
        }

        return $this->isBot($agent) ? 'Crawler' : null;
    }

    protected function platform(string $agent): ?string
    {
        $platforms = [
            'Android' => '~android~i',
            'iOS' => '~(iphone|ipad|ipod)~i',
            'Windows' => '~windows~i',
            'macOS' => '~(macintosh|mac os x)~i',
            'Chrome OS' => '~cros~i',
            'Linux' => '~linux~i',
        ];

        foreach ($platforms as $name => $pattern) {
            if (preg_match($pattern, $agent)) {
                return $name;
            }
        }

        return null;
    }
}
