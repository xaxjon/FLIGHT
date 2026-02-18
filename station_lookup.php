<?php
/**
 * Xaxero Airport, Runway & Frequency Lookup
 *
 * 1. Searches airports.csv for location data.
 * 2. Searches runways.csv for geometry & details.
 * 3. Searches frequencies.csv for radio comms.
 * 4. Returns a combined JSON object.
 */

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$icao = isset($_GET['icao']) ? strtoupper(trim($_GET['icao'])) : '';
$airportFile = 'airports.csv';
$runwayFile = 'runways.csv';
$freqFile = 'frequencies.csv';

if (empty($icao)) {
    echo json_encode(['error' => 'No ICAO provided']);
    exit;
}

if (!file_exists($airportFile) || !file_exists($runwayFile)) {
    echo json_encode(['error' => 'Database files missing on server']);
    exit;
}

// --- Step 1: Find Airport Location ---
$airportData = null;
$handle = fopen($airportFile, "r");
if ($handle) {
    $headers = fgetcsv($handle);
    $idx_ident = array_search('ident', $headers);
    $idx_lat = array_search('latitude_deg', $headers);
    $idx_lon = array_search('longitude_deg', $headers);
    $idx_elev = array_search('elevation_ft', $headers);
    $idx_name = array_search('name', $headers);
    $idx_id = array_search('id', $headers); 

    if ($idx_ident !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            if (strtoupper($row[$idx_ident]) === $icao) {
                $airportData = [
                    'id' => $idx_id !== false ? $row[$idx_id] : null,
                    'icao' => $row[$idx_ident],
                    'name' => $row[$idx_name],
                    'lat'  => (float)$row[$idx_lat],
                    'lon'  => (float)$row[$idx_lon],
                    'elev_ft' => (int)$row[$idx_elev],
                    'elev_m'  => round((int)$row[$idx_elev] * 0.3048),
                    'runways' => [],
                    'freqs' => []
                ];
                break;
            }
        }
    }
    fclose($handle);
}

if (!$airportData) {
    echo json_encode(['error' => 'Station not found']);
    exit;
}

// --- Step 2: Find Runways ---
$handle = fopen($runwayFile, "r");
if ($handle) {
    $headers = fgetcsv($handle);
    $idx_ref = array_search('airport_ident', $headers);
    $idx_len = array_search('length_ft', $headers);
    $idx_wid = array_search('width_ft', $headers);
    $idx_le = array_search('le_ident', $headers);
    $idx_he = array_search('he_ident', $headers);
    
    // Geometry columns
    $idx_le_lat = array_search('le_latitude_deg', $headers);
    $idx_le_lon = array_search('le_longitude_deg', $headers);
    $idx_he_lat = array_search('he_latitude_deg', $headers);
    $idx_he_lon = array_search('he_longitude_deg', $headers);
    $idx_le_hdg = array_search('le_heading_degT', $headers);
    $idx_he_hdg = array_search('he_heading_degT', $headers);

    if ($idx_ref !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            if (strtoupper($row[$idx_ref]) === $icao) {
                
                $le_ident = $row[$idx_le];
                $he_ident = $row[$idx_he];
                
                // Fallback Heading Logic
                $le_hdg_val = ($idx_le_hdg !== false) ? $row[$idx_le_hdg] : '';
                $he_hdg_val = ($idx_he_hdg !== false) ? $row[$idx_he_hdg] : '';

                $le_hdg = (is_numeric($le_hdg_val)) 
                    ? (float)$le_hdg_val 
                    : (intval(preg_replace('/[^0-9]/', '', $le_ident)) * 10);
                
                $he_hdg = (is_numeric($he_hdg_val)) 
                    ? (float)$he_hdg_val 
                    : (intval(preg_replace('/[^0-9]/', '', $he_ident)) * 10);

                // Coordinates for plotting
                $geometry = null;
                if ($idx_le_lat !== false && is_numeric($row[$idx_le_lat])) {
                    $geometry = [
                        'le_lat' => (float)$row[$idx_le_lat],
                        'le_lon' => (float)$row[$idx_le_lon],
                        'he_lat' => (float)$row[$idx_he_lat],
                        'he_lon' => (float)$row[$idx_he_lon]
                    ];
                }

                $airportData['runways'][] = [
                    'ident1' => $le_ident,
                    'ident2' => $he_ident,
                    'length_ft' => (int)$row[$idx_len],
                    'width_ft' => (int)($row[$idx_wid] ?? 100),
                    'heading1' => $le_hdg,
                    'heading2' => $he_hdg,
                    'geometry' => $geometry
                ];
            }
        }
    }
    fclose($handle);
}

// --- Step 3: Find Frequencies ---
if (file_exists($freqFile)) {
    $handle = fopen($freqFile, "r");
    if ($handle) {
        $headers = fgetcsv($handle);
        $idx_ref = array_search('airport_ident', $headers);
        $idx_type = array_search('type', $headers);
        $idx_desc = array_search('description', $headers);
        $idx_mhz = array_search('frequency_mhz', $headers);

        if ($idx_ref !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                if (strtoupper($row[$idx_ref]) === $icao) {
                    $airportData['freqs'][] = [
                        'type' => $row[$idx_type],
                        'desc' => $row[$idx_desc],
                        'mhz'  => $row[$idx_mhz]
                    ];
                }
            }
        }
        fclose($handle);
    }
}

echo json_encode($airportData);
?>