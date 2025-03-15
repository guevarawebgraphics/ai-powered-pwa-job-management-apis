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

// Retrieve machine_id from GET parameter, defaulting to 1 if not provided
$model_number = isset($_GET['modelNumber']) ? intval($_GET['modelNumber']) : 1;

// Prepare the SQL query to retrieve machine details by machine_id
$sql = "SELECT 
            machine_id,
            model_number,
            service_manual,
            parts_diagram,
            service_pointers,
            common_repairs,
            machine_photo,
            brand_name,
            machine_type,
            machine_notes,
            extra_field1,
            extra_field2
        FROM machines
        WHERE model_number = ?";

$stmt = $dbConn->prepare($sql);
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Failed to prepare statement."]);
    exit;
}

// Bind the machine_id parameter and execute the query
$stmt->bind_param("i", $model_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $machine = $result->fetch_assoc();
    // Return JSON response with machine data
    echo json_encode(["status" => "success", "data" => $machine], JSON_PRETTY_PRINT);
} else {
    echo json_encode(["status" => "error", "message" => "No machine found with machine_id $model_number."]);
}

$stmt->close();
$dbConn->close();
?>
