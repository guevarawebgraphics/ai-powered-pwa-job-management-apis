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

// Retrieve gig id and new technician id from GET parameters (or adjust as needed)
$gigID = isset($_GET['gig_id']) ? intval($_GET['gig_id']) : 6; // Default gig id is 6 if not provided
$newTechID = isset($_GET['tech_id']) ? intval($_GET['tech_id']) : 3; // Example default technician id

// Prepare the UPDATE statement to change the technician for the given gig
$sql = "UPDATE gigs SET assigned_tech_id = ? WHERE gig_id = ?";
$stmt = $dbConn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Failed to prepare statement."]);
    exit;
}

// Bind the parameters and execute the statement
$stmt->bind_param("ii", $newTechID, $gigID);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Technician updated successfully for gig_id $gigID."], JSON_PRETTY_PRINT);
} else {
    echo json_encode(["status" => "error", "message" => "Update failed: " . $stmt->error]);
}

$stmt->close();
$dbConn->close();
?>