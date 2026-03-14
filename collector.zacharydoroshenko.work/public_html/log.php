<?php
// collector.zacharydoroshenko.work/log.php
header("Content-Type: application/json");

// 1. CORS Headers - Explicitly allow the test site
header("Access-Control-Allow-Origin: https://test.zacharydoroshenko.work");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 2. Handle the Preflight 'OPTIONS' request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200);
    exit; 
}

// 3. Load Database Config
// Note: Ensure this path is correct based on your folder structure
require_once(__DIR__ . '/../db_config.php');

// 4. Get the raw JSON payload
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if ($data) {
    try {
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // --- DATA MAPPING FIX ---
        // collector-v9.js sends performance metrics inside the 'timing' object.
        // The total page load duration is stored in 'loadEvent'.
        $timing = $data['timing'] ?? [];
        $load_time = $timing['loadEvent'] ?? 0;

        // Fallback: If 'loadEvent' is 0, check if it's a 'page_exit' event 
        // which uses 'timeOnPage' instead.
        if ($load_time <= 0 && isset($data['timeOnPage'])) {
            $load_time = $data['timeOnPage'];
        }

        $sql = "INSERT INTO analytics_logs (session_id, event_type, url, load_time_ms, raw_payload) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['session'] ?? 'unknown',
            $data['type'] ?? 'pageview',
            $data['url'] ?? '',
            $load_time,
            $json
        ]);

        echo json_encode(["status" => "success", "received_load_time" => $load_time]);

    } catch (Exception $e) {
        // If there is a DB error, return the message so you can see it in the Network Tab
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "No data received"]);
}
