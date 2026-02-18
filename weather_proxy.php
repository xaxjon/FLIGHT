<?php
/**
 * Xaxero Weather Proxy
 * * Purpose: 
 * This script fetches weather data from the Open-Meteo API on behalf of the client browser.
 * This effectively bypasses:
 * 1. CORS (Cross-Origin Resource Sharing) restrictions enforced by browsers.
 * 2. Client-side network timeouts (PHP running on the server usually has a better connection).
 * 3. HTTP/HTTPS mixed content issues.
 */

// Disable display_errors to ensure we only output valid JSON
// (Errors are logged to the server error log instead)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set headers to allow the React app to communicate with this script
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 1. Retrieve query parameters from the GET request
$lat = isset($_GET['lat']) ? $_GET['lat'] : '';
$lon = isset($_GET['lon']) ? $_GET['lon'] : '';
$vars = isset($_GET['vars']) ? $_GET['vars'] : '';

// 2. Validate Inputs
if (!$lat || !$lon || !$vars) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters: lat, lon, or vars']);
    exit;
}

// 3. Construct the Open-Meteo API URL
// We use the standard forecast endpoint. 
// Note: We request 'wind_speed_...' and 'wind_direction_...' variables.
$baseUrl = "https://api.open-meteo.com/v1/forecast";
$url = "$baseUrl?latitude=$lat&longitude=$lon&hourly=$vars&wind_speed_unit=kn&forecast_days=1";

// 4. Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
curl_setopt($ch, CURLOPT_TIMEOUT, 30);          // 30-second timeout (robust for server-side)
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow any redirects
curl_setopt($ch, CURLOPT_USERAGENT, 'XaxeroFlightPath/1.0'); // Identifies the app politely

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

// Close the cURL session
curl_close($ch);

// 5. Handle the Response
if ($response === false) {
    // A network error occurred (DNS, Timeout, etc.)
    http_response_code(500);
    echo json_encode([
        'error' => 'Proxy Connection Failed',
        'details' => $curlError,
        'url_attempted' => $url
    ]);
} elseif ($httpCode >= 400) {
    // The API returned an HTTP error (e.g. 400 Bad Request, 429 Rate Limit)
    http_response_code($httpCode);
    // Pass the exact error message from Open-Meteo back to the client for debugging
    echo $response;
} else {
    // Success! Return the weather JSON data
    echo $response;
}
?>