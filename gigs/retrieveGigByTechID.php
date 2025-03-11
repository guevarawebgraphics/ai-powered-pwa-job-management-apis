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
// $technician = 6;
$technician = intval($input_data['techID']);

// SQL query to retrieve gig information along with related client, payee, and user data
$sql = "
    SELECT 
        g.gig_id,
        g.gig_cryptic,
        g.machine_brand,
        g.appliance_type,
        g.model_number,
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
        c.insurance_plan,
        c.email AS client_email,
        c.other_emails,
        c.phone_number AS client_phone_number,
        c.other_phone_numbers,
        c.street_address,
        c.city,
        c.zip_code,
        c.state,
        c.country,
        c.client_notes,
        c.previous_gig_history,
        c.appliances_owned,
        c.maintenance_plan,
        c.payee_id,
        c.extra_field1 AS client_extra_field1,
        c.extra_field2 AS client_extra_field2,
        
        p.payee_id,
        p.payee_name,
        p.payee_last_name,
        p.email AS payee_email,
        p.other_emails AS payee_other_emails,
        p.phone_number AS payee_phone_number,
        p.other_phone_numbers AS payee_other_phone_numbers,
        p.address,
        p.payee_notes,
        p.payee_relation,
        p.extra_field1 AS payee_extra_field1,
        p.extra_field2 AS payee_extra_field2,
        
        u.id AS tech_id,
        u.role_id,
        u.name AS tech_name,
        u.email AS tech_email,
        u.mobile_no AS tech_phone_number
        
    FROM gigs g
    INNER JOIN clients c ON g.client_id = c.client_id
    LEFT JOIN payees p ON c.payee_id = p.payee_id
    INNER JOIN users u ON g.assigned_tech_id = u.id
    WHERE g.assigned_tech_id = $technician
    ORDER BY g.start_datetime DESC
";

// Execute the query
$result = $dbConn->query($sql);
if ($result->num_rows > 0) {
    $gigs = [];
    
    while ($row = $result->fetch_assoc()) {
        $gigs[] = $row;
    }

    // Return JSON response
    echo json_encode(["status" => "success", "data" => $gigs], JSON_PRETTY_PRINT);
} else {
    echo json_encode(["status" => "error", "message" => "No gigs found."]);
}
?>