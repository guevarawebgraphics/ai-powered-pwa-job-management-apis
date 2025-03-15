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

// Retrieve gig ID from GET parameters
$gigID = isset($_GET['gig_id']) ? intval($_GET['gig_id']) : null;

if (!$gigID) {
    echo json_encode(["status" => "error", "message" => "Gig ID is required."]);
    exit;
}

// Fetch the current time_started and time_ended values
$sql = "SELECT time_started, time_ended FROM gigs WHERE gig_id = ?";
$stmt = $dbConn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Failed to prepare select statement."]);
    exit;
}

$stmt->bind_param("i", $gigID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Gig not found."]);
    exit;
}

$gig = $result->fetch_assoc();
$stmt->close();

$time_started = $gig['time_started'];
$time_ended = $gig['time_ended'];

if (is_null($time_started)) {
    // If time_started is null, update it with the current timestamp
    $updateSql = "UPDATE gigs SET time_started = NOW() WHERE gig_id = ?";
    $message = "Gig has been successfully started.";
} elseif (!is_null($time_started) && is_null($time_ended)) {
    // If time_started is set and time_ended is null, update time_ended
    $updateSql = "UPDATE gigs SET time_ended = NOW() WHERE gig_id = ?";
    $message = "Gig has been successfully ended.";
} else {
    // If both time_started and time_ended are already set, do nothing
    echo json_encode(["status" => "ignored", "message" => "Gig has already been completed."]);
    exit;
}

// Prepare and execute the update statement
$stmt = $dbConn->prepare($updateSql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Failed to prepare update statement."]);
    exit;
}

$stmt->bind_param("i", $gigID);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => $message], JSON_PRETTY_PRINT);
} else {
    echo json_encode(["status" => "error", "message" => "Update failed: " . $stmt->error]);
}

$stmt->close();
$dbConn->close();
?>
