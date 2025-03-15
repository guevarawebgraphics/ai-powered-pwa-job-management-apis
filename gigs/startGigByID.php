<?php
// Database configuration
include '../config/dbConn.php';

$expected_token = getenv('API_KEY'); // Store securely (e.g., in ENV variables)
$headers = getallheaders();

if (!isset($headers['Authorization']) || $headers['Authorization'] !== "Bearer $expected_token") {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

// Check connection
if ($dbConn->connect_error) {
    die('Connect Error (' . $dbConn->connect_errno . ') ' . $dbConn->connect_error);
}

// Set the charset to utf8mb4 for proper encoding
$dbConn->set_charset("utf8mb4");

// Retrieve gig id from GET parameters
$gigID = isset($_GET['gig_id']) ? intval($_GET['gig_id']) : null;

if (!$gigID) {
    echo json_encode(["status" => "error", "message" => "Gig ID is required."]);
    exit;
}

// Use NOW() to set the current timestamp
$sql = "UPDATE gigs SET time_started = NOW() WHERE gig_id = ?";
$stmt = $dbConn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Failed to prepare statement."]);
    exit;
}

// Bind the gig ID parameter and execute the statement
$stmt->bind_param("i", $gigID);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Gig has been successfully started for Gig ID: $gigID."], JSON_PRETTY_PRINT);
} else {
    echo json_encode(["status" => "error", "message" => "Update failed: " . $stmt->error]);
}

$stmt->close();
$dbConn->close();
?>
