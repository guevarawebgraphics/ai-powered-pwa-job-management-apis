<?php
// Database configuration
include '../config/dbConn.php';

$expected_token = getenv('API_KEY');
$headers = getallheaders();

if (!isset($headers['Authorization']) || $headers['Authorization'] !== "Bearer $expected_token" || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

if ($dbConn->connect_error) {
    die('Connect Error (' . $dbConn->connect_errno . ') ' . $dbConn->connect_error);
}

$input_data = json_decode(file_get_contents("php://input"), true);
if (!isset($input_data['techID']) || !is_numeric($input_data['techID'])) {
    echo json_encode(["status" => "error", "message" => "Invalid or missing techID"]);
    exit;
}

$dbConn->set_charset("utf8mb4");

$technician = $input_data['techID'];
$date_filter = (isset($input_data['date']) && !empty($input_data['date']))
    ? $input_data['date']
    : date('Y-m-d');

$current_datetime = $input_data['current_datetime'];
$current_date = date('Y-m-d', strtotime($current_datetime));

// Initialize SQL components
$bind_types = "";
$bind_params = [];
$sql = "SELECT 
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
        WHERE g.assigned_tech_id = ? ";
$bind_types .= "s";
$bind_params[] = $technician;

if ($date_filter == $current_date) {
    // When the passed date is today, retrieve gigs for today that are still within the 2-hour grace period,
    // plus any gigs scheduled for future dates.
    $sql .= " AND ( (DATE(g.start_datetime) = ? AND ? < DATE_ADD(g.start_datetime, INTERVAL 2 HOUR)) 
                  OR DATE(g.start_datetime) > ? ) ";
    $bind_types .= "sss";
    $bind_params[] = $current_date;
    $bind_params[] = $current_datetime;
    $bind_params[] = $current_date;
} else {
    // For past or future dates, only gigs that exactly match the passed date will be returned.
    $sql .= " AND DATE(g.start_datetime) = ? ";
    $bind_types .= "s";
    $bind_params[] = $date_filter;
}

$sql .= " AND g.gig_complete != 3
          ORDER BY g.start_datetime ASC ";

if (isset($input_data['total_records']) && is_numeric($input_data['total_records'])) {
    $total_records = intval($input_data['total_records']);
    $sql .= " LIMIT " . $total_records;
}

$stmt = $dbConn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: (" . $dbConn->errno . ") " . $dbConn->error);
}

$stmt->bind_param($bind_types, ...$bind_params);
$stmt->execute();
$result = $stmt->get_result();
$gigs = [];

if ($result->num_rows > 0) {
    while ($gig = $result->fetch_assoc()) {
        $model_number = $gig['model_number'];
        $machine_sql = "SELECT * FROM machines WHERE model_number = ?";
        $stmt_machine = $dbConn->prepare($machine_sql);
        $stmt_machine->bind_param("s", $model_number);
        $stmt_machine->execute();
        $machine_result = $stmt_machine->get_result();
        $gig['machine'] = ($machine_result->num_rows > 0) ? $machine_result->fetch_assoc() : null;
        $gigs[] = $gig;
    }
    echo json_encode(["status" => "success", "data" => $gigs], JSON_PRETTY_PRINT);
} else {
    echo json_encode(["status" => "error", "message" => "No gigs found."]);
}
?>
