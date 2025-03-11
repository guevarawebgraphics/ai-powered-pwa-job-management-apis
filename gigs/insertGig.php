<?php
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
    die("Connection failed: " . $dbConn->connect_error);
}

// Function to handle NULL values
function setNull($value) {
    return ($value !== null) ? $value : null;
}

// Define test data with some NULL values
$gig_cryptic = "GIG12345";
$machine_brand = "BrandX";
$appliance_type = "Top Load Washer";
$model_number = "ModelX-1000";
$serial_number = "SN1234567890";
$initial_issue = "The machine is overheating after prolonged use.";
$top_recommended_repairs = json_encode([
    "replace_cooling_fan" => "Replace the faulty cooling fan to prevent overheating.",
    "clean_internal_components" => "Thoroughly clean all internal components to remove dust buildup."
]);
$gig_price = 250.00;
$gig_discount = 25.00;
$trainee_included = null;
$resolution = null;
$start_datetime = '2025-01-10 09:00:00';
$repair_notes = "Initial diagnostics completed. Awaiting replacement parts.";
$qb_invoice_num = 112;
$child_of_gig = null;
$invoice_paid = 0;
$gig_complete = 0;
$parts_used = null;
$time_started = null;
$time_ended = null;
$extra_field1 = null;
$extra_field2 = "Additional warranty included.";

// Prepare the SQL statement
$sql = "INSERT INTO `gigs` (
            `gig_cryptic`, 
            `machine_brand`, 
            `appliance_type`,
            `model_number`, 
            `serial_number`, 
            `initial_issue`, 
            `top_recommended_repairs`, 
            `gig_price`, 
            `gig_discount`, 
            `trainee_included`, 
            `resolution`, 
            `start_datetime`, 
            `repair_notes`, 
            `qb_invoice_num`,
            `child_of_gig`,
            `invoice_paid`,
            `gig_complete`,
            `parts_used`,
            `time_started`,
            `time_ended`,
            `extra_field1`, 
            `extra_field2`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

// Prepare the statement
$stmt = $dbConn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: (" . $dbConn->errno . ") " . $dbConn->error);
}

// Bind parameters
$stmt->bind_param(
    "ssssssddsssssisiiissssss",
    $gig_cryptic,            // s
    $machine_brand,          // s
    $appliance_type,         // s
    $model_number,           // s
    $serial_number,          // s
    $initial_issue,          // s
    $top_recommended_repairs,// s
    $gig_price,              // d
    $gig_discount,           // d
    $trainee_included,       // s (NULL allowed)
    $resolution,             // s (NULL allowed)
    $start_datetime,         // s
    $repair_notes,           // s
    $qb_invoice_num,         // i
    $child_of_gig,           // i (NULL allowed)
    $invoice_paid,           // i
    $gig_complete,           // i
    $parts_used,             // s (NULL allowed)
    $time_started,           // s (NULL allowed)
    $time_ended,             // s (NULL allowed)
    $extra_field1,           // s (NULL allowed)
    $extra_field2            // s
);

// Execute the statement
if ($stmt->execute()) {
    echo "New gig inserted successfully with ID: " . $stmt->insert_id;
} else {
    echo "Error inserting gig: (" . $stmt->errno . ") " . $stmt->error;
}

// Close the statement and connection
$stmt->close();
$dbConn->close();
?>