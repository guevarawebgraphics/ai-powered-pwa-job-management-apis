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
$gigID = isset($_GET['gig_id']) ? intval($_GET['gig_id']) : 6;

// Fetch gigs
$sql = "
    SELECT 
        g.gig_id, g.gig_cryptic, g.machine_brand, g.appliance_type, 
        g.model_number, g.customer_input, g.serial_number, g.initial_issue, 
        g.top_recommended_repairs, g.gig_price, g.gig_discount, g.trainee_included, 
        g.resolution, g.start_datetime, g.repair_notes, g.qb_invoice_num, 
        g.child_of_gig, g.invoice_paid, g.gig_complete, g.parts_used, 
        g.time_started, g.time_ended, g.extra_field1 AS gig_extra_field1, 
        g.extra_field2 AS gig_extra_field2, g.created_at, g.updated_at,
        g.gig_report_images, g.youtube_link, g.gig_price_detail, g.gig_type,

        c.client_id, c.client_name, c.client_last_name, c.insurance_plan, 
        c.email AS client_email, c.other_emails, c.phone_number AS client_phone_number, 
        c.other_phone_numbers, c.street_address, c.city, c.zip_code, c.state, 
        c.country, c.client_notes, c.previous_gig_history, c.appliances_owned, 
        c.maintenance_plan, c.payee_id, c.extra_field1 AS client_extra_field1, 
        c.extra_field2 AS client_extra_field2,

        p.payee_id, p.payee_name, p.payee_last_name, p.email AS payee_email, 
        p.other_emails AS payee_other_emails, p.phone_number AS payee_phone_number, 
        p.other_phone_numbers AS payee_other_phone_numbers, p.address, p.payee_notes, 
        p.payee_relation, p.extra_field1 AS payee_extra_field1, 
        p.extra_field2 AS payee_extra_field2,

        u.id AS tech_id, u.role_id, u.name AS tech_name, u.first_name AS tech_first_name, u.last_name AS tech_last_name, u.email AS tech_email, u.rank_type AS tech_rank_type,
        u.mobile_no AS tech_phone_number

    FROM gigs g
    INNER JOIN clients c ON g.client_id = c.client_id
    LEFT JOIN payees p ON c.payee_id = p.payee_id
    INNER JOIN users u ON g.assigned_tech_id = u.id
    WHERE g.gig_id = ?
    ORDER BY g.start_datetime DESC
";

$stmt = $dbConn->prepare($sql);
$stmt->bind_param("i", $gigID);
$stmt->execute();
$result = $stmt->get_result();

$gigs = [];
$modelNumbers = [];

while ($gig = $result->fetch_assoc()) {
    $modelNumbers[] = $gig['model_number']; // Collect model numbers
    $gigs[] = $gig;
}

$stmt->close();

// Fetch machine details for all collected model numbers
if (!empty($modelNumbers)) {
    $placeholders = implode(',', array_fill(0, count($modelNumbers), '?'));
    $machine_sql = "SELECT * FROM machines WHERE model_number IN ($placeholders)";

    $stmt = $dbConn->prepare($machine_sql);
    $stmt->bind_param(str_repeat('s', count($modelNumbers)), ...$modelNumbers);
    $stmt->execute();
    $machine_result = $stmt->get_result();

    $machines = [];
    while ($machine = $machine_result->fetch_assoc()) {
        $machines[$machine['model_number']] = $machine; // Group by model_number
    }
    $stmt->close();

    // Assign machine details to corresponding gigs
    foreach ($gigs as &$gig) {
        $gig['machine'] = $machines[$gig['model_number']] ?? null;
    }
}



$client_id = $gig['client_id']; // Extract client_id
// Total Gig Price Per Client
$total_price_query = "SELECT SUM(gig_price) AS client_total_gig_price FROM gigs WHERE client_id = ?";
$total_price_stmt = $dbConn->prepare($total_price_query);
$total_price_stmt->bind_param("i", $client_id);
$total_price_stmt->execute();
$total_price_result = $total_price_stmt->get_result();
$total_price_row = $total_price_result->fetch_assoc();
$total_gig_price = $total_price_row['client_total_gig_price'] ?? 0; // Default to 0 if NULL
$total_price_stmt->close();
// Add total_gig_price to response
$gig['client_total_gig_price'] = $total_gig_price;


// Return JSON response
echo json_encode(["status" => "success", "data" => $gigs], JSON_PRETTY_PRINT);
?>
