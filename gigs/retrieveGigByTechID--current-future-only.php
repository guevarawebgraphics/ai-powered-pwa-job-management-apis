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
    die('Connect Error (' . $dbConn->connect_errno . ') ' . $dbConn->connect_error);
}

// Retrieve input data
$input_data = json_decode(file_get_contents("php://input"), true);
if (!isset($input_data['techID']) || !is_numeric($input_data['techID'])) {
    echo json_encode(["status" => "error", "message" => "Invalid or missing techID"]);
    exit;
}

// Set charset
$dbConn->set_charset("utf8mb4");

$technician = intval($input_data['techID']);

$date_filter = isset($input_data['date']) && !empty($input_data['date'])
    ? $input_data['date']
    : date('Y-m-d');

$time_filter = isset($input_data['time']) && !empty($input_data['time'])
    ? $input_data['time']
    : null;

    
// Filter by Date & Time
// $sql = "
//     SELECT 
//         g.gig_id,
//         g.gig_cryptic,
//         g.machine_brand,
//         g.appliance_type,
//         g.model_number,
//         g.customer_input,
//         g.serial_number,
//         g.initial_issue,
//         g.top_recommended_repairs,
//         g.gig_price,
//         g.gig_price_detail,
//         g.gig_discount,
//         g.trainee_included,
//         g.resolution,
//         g.start_datetime,
//         g.repair_notes,
//         g.qb_invoice_num,
//         g.child_of_gig,
//         g.invoice_paid,
//         g.gig_complete,
//         g.parts_used,
//         g.time_started,
//         g.time_ended,
//         g.extra_field1 AS gig_extra_field1,
//         g.extra_field2 AS gig_extra_field2,
//         g.created_at,
//         g.updated_at,
//         g.youtube_link,

//         c.client_id,
//         c.client_name,
//         c.client_last_name,
//         c.email AS client_email,
//         c.phone_number AS client_phone_number,
//         c.street_address,
//         c.city,
//         c.state,

//         u.id AS tech_id,
//         u.name AS tech_name,
//         u.email AS tech_email
//     FROM gigs g
//     INNER JOIN clients c ON g.client_id = c.client_id
//     INNER JOIN users u ON g.assigned_tech_id = u.id
//     WHERE g.assigned_tech_id = ? 
// ";

// if ($time_filter) {
//     $sql .= "AND DATE(g.start_datetime) = ? AND TIME(g.start_datetime) >= ? ";
// } else {
//     $sql .= "AND DATE(g.start_datetime) = ? ";
// }


// $sql .= "AND g.gig_complete != 3
//     ORDER BY g.start_datetime DESC";

// $stmt = $dbConn->prepare($sql);

// if ($time_filter) {
//     $stmt->bind_param("iss", $technician, $date_filter, $time_filter);
// } else {
//     $stmt->bind_param("is", $technician, $date_filter);
// }

$current_datetime = $input_data['current_datetime']; // UTC Time passed from Frontend Axios Vue

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
        g.gig_price_detail,
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
        g.youtube_link,

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
    WHERE g.assigned_tech_id = ? 
      AND DATE(g.start_datetime) = ?
      AND ? < DATE_ADD(g.start_datetime, INTERVAL 2 HOUR)
      AND g.gig_complete != 3
    ORDER BY g.start_datetime DESC";

    // If total_records is provided and valid, append the LIMIT clause directly
if (isset($input_data['total_records']) && is_numeric($input_data['total_records'])) {
    $total_records = intval($input_data['total_records']);
    $sql .= " LIMIT " . $total_records;
}


$stmt = $dbConn->prepare($sql);
$stmt->bind_param("iss", $technician, $date_filter, $current_datetime);

$stmt->execute();
$result = $stmt->get_result();
$gigs = [];

if ($result->num_rows > 0) {
    while ($gig = $result->fetch_assoc()) {
        $model_number = $gig['model_number'];

        // Query to get machine details
        $machine_sql = "SELECT * FROM machines WHERE model_number = ?";
        $stmt_machine = $dbConn->prepare($machine_sql);
        $stmt_machine->bind_param("s", $model_number);
        $stmt_machine->execute();
        $machine_result = $stmt_machine->get_result();

        // Fetch machine details if available
        $gig['machine'] = $machine_result->num_rows > 0 ? $machine_result->fetch_assoc() : null;

        $gigs[] = $gig;
    }

    echo json_encode(["status" => "success", "data" => $gigs], JSON_PRETTY_PRINT);
} else {
    echo json_encode(["status" => "error", "message" => "No gigs found."]);
}
?>
