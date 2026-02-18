<?php
/**
 * Aviation Weather Proxy
 * Place alongside index.html and station_lookup.php.
 * Fetches server-to-server to bypass browser CORS restrictions.
 * Cache headers are explicitly disabled at every layer.
 */

// --- Allowlist: only these base URLs may be fetched ---
$ALLOWED_PREFIXES = [
    'https://aviationweather.gov/api/data/metar',
    'https://aviationweather.gov/api/data/taf',
    'https://tgftp.nws.noaa.gov/data/observations/metar/',
];

// Kill any caching at the browser, proxy, and CDN level
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Access-Control-Allow-Origin: *');

$url = isset($_GET['url']) ? trim($_GET['url']) : '';

if (!$url) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing url parameter']);
    exit;
}

// Validate against allowlist
$allowed = false;
foreach ($ALLOWED_PREFIXES as $prefix) {
    if (strpos($url, $prefix) === 0) {
        $allowed = true;
        break;
    }
}

if (!$allowed) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'URL not in allowlist']);
    exit;
}

// Append a cache-buster to the upstream URL so aviationweather.gov
// doesn't serve a cached response from their own edge/CDN layer
$separator = (strpos($url, '?') !== false) ? '&' : '?';
$url .= $separator . '_nocache=' . time();

// Fetch with cache-defeating request headers
$ctx = stream_context_create([
    'http' => [
        'timeout'        => 12,
        'ignore_errors'  => true,
        'user_agent'     => 'Mozilla/5.0 (compatible; XaxeroWeatherProxy/1.0)',
        'header'         =>
            "Accept: application/json, text/plain, */*\r\n" .
            "Cache-Control: no-cache, no-store\r\n" .
            "Pragma: no-cache\r\n",
    ],
    'ssl' => [
        'verify_peer'      => true,
        'verify_peer_name' => true,
    ],
]);

$body = @file_get_contents($url, false, $ctx);

if ($body === false) {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to fetch remote URL']);
    exit;
}

// Pass through the upstream HTTP status code
$statusLine = $http_response_header[0] ?? 'HTTP/1.1 200 OK';
preg_match('/\d{3}/', $statusLine, $m);
http_response_code(isset($m[0]) ? (int)$m[0] : 200);

// Mirror the upstream Content-Type
$contentType = 'text/plain; charset=utf-8';
foreach ($http_response_header as $h) {
    if (stripos($h, 'Content-Type:') === 0) {
        $contentType = trim(substr($h, 13));
        break;
    }
}
header('Content-Type: ' . $contentType);

echo $body;
