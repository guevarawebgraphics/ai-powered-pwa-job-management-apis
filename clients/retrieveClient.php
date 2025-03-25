<?php
include '../config/dbConn.php';

$expected_token = getenv('API_KEY');
$headers = getallheaders();

if (!isset($headers['Authorization']) || $headers['Authorization'] !== "Bearer $expected_token" && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

if ($dbConn->connect_error) {
    die("Connection failed: " . $dbConn->connect_error);
}

$input_data = json_decode(file_get_contents("php://input"), true);
if (!isset($input_data['client_id']) || !is_numeric($input_data['client_id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid or missing client_id"]);
    exit;
}

$client_id = intval($input_data['client_id']);

// Retrieve client details
$sql = "SELECT 
            `client_id`, `client_name`, `insurance_plan`, `email`, `other_emails`,
            `phone_number`, `other_phone_numbers`, `street_address`, `city`,
            `zip_code`, `state`, `country`, `client_notes`, `appliances_owned`,
            `maintenance_plan`, `payee_id`, `extra_field1`, `extra_field2`
        FROM `clients` 
        WHERE `client_id` = ?";

$stmt = $dbConn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $dbConn->error);
}

$stmt->bind_param("i", $client_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "No client found with ID: $client_id"]);
} else {
    $client = $result->fetch_assoc();

    // Retrieve gig history for this client
    $gig_query = "SELECT * FROM `gigs` WHERE `client_id` = ? ORDER BY `start_datetime` DESC";
    $gig_stmt = $dbConn->prepare($gig_query);
    if ($gig_stmt === false) {
        die("Prepare failed: " . $dbConn->error);
    }

    $gig_stmt->bind_param("i", $client_id);
    if ($gig_stmt->execute()) {
        $gig_result = $gig_stmt->get_result();
        $client['gigs'] = [];
        while ($gig = $gig_result->fetch_assoc()) {
            $client['gigs'][] = $gig;
        }
    }
    $gig_stmt->close();

    // Extract appliances_owned (assuming comma-separated machine IDs)
    $machine_ids = array_filter(array_map('intval', explode(',', $client['appliances_owned'])));

    // Initialize machines array
    $client['machines'] = [];
    if (!empty($machine_ids)) {
        $placeholders = implode(',', array_fill(0, count($machine_ids), '?'));
        $machine_query = "SELECT * FROM `machines` WHERE `machine_id` IN ($placeholders)";

        $machine_stmt = $dbConn->prepare($machine_query);
        if ($machine_stmt === false) {
            // die("Prepare failed: " . $dbConn->error);
            echo json_encode(["status" => "error", "message" => $dbConn->error]);
            exit;
        }

        $types = str_repeat('i', count($machine_ids));
        $machine_stmt->bind_param($types, ...$machine_ids);

        if ($machine_stmt->execute()) {
            $machine_result = $machine_stmt->get_result();
            while ($machine = $machine_result->fetch_assoc()) {
                $client['machines'][] = $machine;
            }
        }
        $machine_stmt->close();
    }

    echo json_encode(["status" => "success", "data" => $client], JSON_PRETTY_PRINT);
}

$stmt->close();
$dbConn->close();
?>
