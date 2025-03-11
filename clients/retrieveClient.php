<?php
// Assuming $dbConn is your existing mysqli connection
// Example: $dbConn = new mysqli($host, $username, $password, $database);
include '../config/dbConn.php';

$expected_token = getenv('API_KEY'); // Store securely (e.g., in ENV variables)
$headers = getallheaders();

if (!isset($headers['Authorization']) || $headers['Authorization'] !== "Bearer $expected_token"  && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}


// Check connection
if ($dbConn->connect_error) {
    die("Connection failed: " . $dbConn->connect_error);
}
// Retrieve techID from POST request
$input_data = json_decode(file_get_contents("php://input"), true);
if (!isset($input_data['client_id']) || !is_numeric($input_data['client_id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid or missing client_id"]);
    exit;
}



// Retrieve and sanitize the client_id from GET or POST
$client_id = intval($input_data['client_id']);;
// $client_id = 5;

if ($client_id === null) {
    die("No client ID provided.");
}

// Prepare the SQL statement
$sql = "SELECT 
            `client_id`, `client_name`, `insurance_plan`, `email`, `other_emails`,
            `phone_number`, `other_phone_numbers`, `street_address`, `city`,
            `zip_code`, `state`, `country`, `client_notes`, `previous_gig_history`,
            `appliances_owned`, `maintenance_plan`, `payee_id`, `extra_field1`, `extra_field2`
        FROM `clients` 
        WHERE `client_id` = ?";

// Initialize prepared statement
$stmt = $dbConn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $dbConn->error);
}

// Bind the parameter
$stmt->bind_param("i", $client_id);

// Execute the statement
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

// Get the result
$result = $stmt->get_result();

// Check if client exists
if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "No client found with ID: $client_id"]);
} else {
    // Fetch associative array
    $client = $result->fetch_assoc();

    // Return client data in JSON format
    echo json_encode(["status" => "success", "data" => $client], JSON_PRETTY_PRINT);
}

// Close the statement
$stmt->close();

// Close the connection (optional, if not reused)
$dbConn->close();
?>
