<?php
// Database configuration
include '../config/dbConn.php';

$expected_token = getenv('API_KEY'); // Store securely (e.g., in ENV variables)
$headers = getallheaders();

if (!isset($headers['Authorization']) || $headers['Authorization'] !== "Bearer $expected_token" && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

// Check connection
if ($dbConn->connect_error) {
    die('Connect Error (' . $dbConn->connect_errno . ') '
            . $dbConn->connect_error);
}

// Retrieve techID from POST request
$input_data = json_decode(file_get_contents("php://input"), true);
if (!isset($input_data['techID']) || !is_numeric($input_data['techID'])) {
    echo json_encode(["status" => "error", "message" => "Invalid or missing techID"]);
    exit;
}

// Set the charset to utf8mb4 for proper encoding
$dbConn->set_charset("utf8mb4");

$technician = intval($input_data['techID']);

// SQL query to retrieve gigs assigned to the technician
$sql = "
    SELECT 
        g.gig_id,
        g.gig_cryptic,
        g.machine_brand,
        g.appliance_type,
        g.model_number,
        g.customer_input,
        g.serial_number,
        g.initial_issue,
        g.top_recommended_repairs,
        g.gig_price,
        g.gig_discount,
        g.trainee_included,
        g.resolution,
        g.start_datetime,
        g.repair_notes,
        g.qb_invoice_num,
        g.child_of_gig,
        g.invoice_paid,
        g.gig_complete,
        g.parts_used,
        g.time_started,
        g.time_ended,
        g.extra_field1 AS gig_extra_field1,
        g.extra_field2 AS gig_extra_field2,
        g.created_at,
        g.updated_at,

        c.client_id,
        c.client_name,
        c.client_last_name,
        c.email AS client_email,
        c.phone_number AS client_phone_number,
        c.street_address,
        c.city,
        c.state,

        u.id AS tech_id,
        u.name AS tech_name,
        u.email AS tech_email

    FROM gigs g
    INNER JOIN clients c ON g.client_id = c.client_id
    INNER JOIN users u ON g.assigned_tech_id = u.id
    WHERE g.assigned_tech_id = $technician AND DATE(g.created_at) = CURDATE()
    ORDER BY g.start_datetime DESC
";

// Execute the query
$result = $dbConn->query($sql);
$gigs = [];

if ($result->num_rows > 0) {
    while ($gig = $result->fetch_assoc()) {
        $model_number = $gig['model_number'];

        // Query to get machine details
        $machine_sql = "SELECT * FROM machines WHERE model_number = ?";
        $stmt = $dbConn->prepare($machine_sql);
        $stmt->bind_param("s", $model_number);
        $stmt->execute();
        $machine_result = $stmt->get_result();

        // Fetch machine details if available
        if ($machine_result->num_rows > 0) {
            $gig['machine'] = $machine_result->fetch_assoc();
        } else {
            $gig['machine'] = null;
        }

        $gigs[] = $gig;
    }

    // Return JSON response with nested machines
    echo json_encode(["status" => "success", "data" => $gigs], JSON_PRETTY_PRINT);
} else {
    echo json_encode(["status" => "error", "message" => "No gigs found."]);
}

