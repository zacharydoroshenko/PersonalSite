<?php
// reporting.zacharydoroshenko.work/api.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once(__DIR__ . '/../db_config.php');

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = explode('/', trim($_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'], '/'));

// Check if the request is for the 'api' prefix, then get the resource
// Expected URL: /api.php/static or /api.php/static/1
$resource = ($requestUri[0] === 'api') ? ($requestUri[1] ?? null) : ($requestUri[0] ?? null);
$id = ($requestUri[0] === 'api') ? ($requestUri[2] ?? null) : ($requestUri[1] ?? null);

if ($resource !== 'static') {
    http_response_code(404);
    echo json_encode(["error" => "Route not found"]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    switch ($method) {
        case 'GET':
            if ($id) {
                // GET /api/static/{id} - Retrieve specific entry
                $stmt = $pdo->prepare("SELECT * FROM analytics_logs WHERE id = ?");
                $stmt->execute([$id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($result ? $result : ["message" => "Not found"]);
            } else {
                // GET /api/static - Retrieve all entries
                $stmt = $pdo->query("SELECT * FROM analytics_logs ORDER BY created_at DESC");
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($result);
            }
            break;

        case 'POST':
            // POST /api/static - Add new entry (No ID)
            $json = file_get_contents("php://input");
            $data = json_decode($json, true);

            if (!$data) {
                http_response_code(400);
                echo json_encode(["error" => "Invalid JSON data"]);
                break;
            }

            // Map collector.js payload fields to your database columns
            $sql = "INSERT INTO analytics_logs (session_id, event_type, url, user_agent, load_time_ms, raw_payload) 
                    VALUES (:sid, :type, :url, :ua, :load, :raw)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':sid'  => $data['session'] ?? null,
                ':type' => $data['type'] ?? 'unknown',
                ':url'  => $data['url'] ?? null,
                ':ua'   => $data['technographics']['userAgent'] ?? null,
                ':load' => $data['timing']['totalLoadTime'] ?? 0,
                ':raw'  => $json // Store full JSON for future Phase 2 analysis
            ]);

            echo json_encode(["message" => "Data ingested successfully", "id" => $pdo->lastInsertId()]);
            break;

        case 'PUT':
            // PUT /api/static/{id} - Update entry
            if (!$id) {
                http_response_code(400);
                echo json_encode(["error" => "ID required for PUT"]);
                break;
            }
            $data = json_decode(file_get_contents("php://input"), true);
            
            // Simple example: updating the event type
            $stmt = $pdo->prepare("UPDATE analytics_logs SET event_type = ? WHERE id = ?");
            $stmt->execute([$data['type'] ?? 'updated', $id]);
            
            echo json_encode(["message" => "Record $id updated"]);
            break;

        case 'DELETE':
            // DELETE /api/static/{id} - Delete specific entry
            if (!$id) {
                http_response_code(400);
                echo json_encode(["error" => "ID required for DELETE"]);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM analytics_logs WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(["message" => "Record $id deleted"]);
            break;

        default:
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed"]);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
