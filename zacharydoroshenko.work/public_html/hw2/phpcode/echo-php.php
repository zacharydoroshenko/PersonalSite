<?php
// 1. Required Headers
header("Cache-Control: no-cache");
header("Content-Type: text/html");

// 2. Collect Metadata (Equivalent to the Perl %ENV keys)
$protocol   = $_SERVER['SERVER_PROTOCOL'] ?? 'N/A';
$method     = $_SERVER['REQUEST_METHOD'] ?? 'N/A';
$query      = $_SERVER['QUERY_STRING'] ?? 'N/A';
$hostname   = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$date_time  = date('l, F j, Y H:i:s');

// 3. Read the Message Body (Standard Input equivalent)
// php://input allows us to read raw data from POST, PUT, or DELETE
$message_body = file_get_contents('php://input');

// 4. Output the HTML (Using a Heredoc for clean formatting)
echo <<<END
<!DOCTYPE html>
<html>
<head>
    <title>PHP General Request Echo</title>
</head>
<body>
    <h1 align="center">PHP General Request Echo</h1>
    <hr>
    <p><b>Hostname:</b> $hostname</p>
    <p><b>Date/Time:</b> $date_time</p>
    <p><b>User Agent:</b> $user_agent</p>
    <p><b>Your IP:</b> $ip_address</p>
    <hr>
    <p><b>HTTP Protocol:</b> $protocol</p>
    <p><b>HTTP Method:</b> $method</p>
    <p><b>Query String:</b> $query</p>
    <p><b>Message Body:</b> $message_body</p>
    <br>
    <a href="/echo-form.html">Back to Form</a>
</body>
</html>
END;
?>