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
    die('Connect Error (' . $dbConn->connect_errno . ') '
            . $dbConn->connect_error);
}

// Set the charset to utf8mb4 for proper encoding
$dbConn->set_charset("utf8mb4");

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
        p.email AS payee_email,
        p.other_emails AS payee_other_emails,
        p.phone_number AS payee_phone_number,
        p.other_phone_numbers AS payee_other_phone_numbers,
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
    ORDER BY g.start_datetime DESC
";

// Execute the query
if ($result = $dbConn->query($sql)) {
    // Check if there are any gigs
    if ($result->num_rows > 0) {
        // Start HTML output
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gigs Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .gig-container {
            border: 1px solid #ccc;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .gig-container h2 {
            margin-top: 0;
        }
        .field-label {
            font-weight: bold;
            display: inline-block;
            width: 200px;
            vertical-align: top;
        }
        .field-value {
            display: inline-block;
            width: calc(100% - 220px);
        }
        .section {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <h1>Gigs Information</h1>';

        // Loop through each gig
        while ($row = $result->fetch_assoc()) {
            echo '<div class="gig-container">';
            echo '<h2>Gig ID: ' . htmlspecialchars($row['gig_id']) . ' - ' . htmlspecialchars($row['gig_cryptic']) . '</h2>';
            
            // Gig Details
            echo '<div class="section"><h3>Gig Details</h3>';
            echo '<p><span class="field-label">Machine Brand:</span><span class="field-value">' . htmlspecialchars($row['machine_brand']) . '</span></p>';
            echo '<p><span class="field-label">Model Number:</span><span class="field-value">' . htmlspecialchars($row['model_number']) . '</span></p>';
            echo '<p><span class="field-label">Serial Number:</span><span class="field-value">' . htmlspecialchars($row['serial_number']) . '</span></p>';
            echo '<p><span class="field-label">Initial Issue:</span><span class="field-value">' . nl2br(htmlspecialchars($row['initial_issue'])) . '</span></p>';
            echo '<p><span class="field-label">Top Recommended Repairs:</span><span class="field-value"><pre>' . htmlspecialchars($row['top_recommended_repairs']) . '</pre></span></p>';
            echo '<p><span class="field-label">Gig Price:</span><span class="field-value">$' . number_format($row['gig_price'], 2) . '</span></p>';
            echo '<p><span class="field-label">Gig Discount:</span><span class="field-value">$' . number_format($row['gig_discount'], 2) . '</span></p>';
            echo '<p><span class="field-label">Trainee Included:</span><span class="field-value">' . htmlspecialchars($row['trainee_included']) . '</span></p>';
            echo '<p><span class="field-label">Resolution:</span><span class="field-value">' . nl2br(htmlspecialchars($row['resolution'])) . '</span></p>';
            echo '<p><span class="field-label">Start Date & Time:</span><span class="field-value">' . htmlspecialchars($row['start_datetime']) . '</span></p>';
            echo '<p><span class="field-label">Repair Notes:</span><span class="field-value">' . nl2br(htmlspecialchars($row['repair_notes'])) . '</span></p>';
            echo '<p><span class="field-label">Extra Field 1:</span><span class="field-value">' . htmlspecialchars($row['gig_extra_field1']) . '</span></p>';
            echo '<p><span class="field-label">Extra Field 2:</span><span class="field-value">' . htmlspecialchars($row['gig_extra_field2']) . '</span></p>';
            echo '<p><span class="field-label">Extra Field 3:</span><span class="field-value">' . htmlspecialchars($row['gig_completed']) . '</span></p>';
            echo '<p><span class="field-label">Created At:</span><span class="field-value">' . htmlspecialchars($row['created_at']) . '</span></p>';
            echo '<p><span class="field-label">Updated At:</span><span class="field-value">' . htmlspecialchars($row['updated_at']) . '</span></p>';
            echo '</div>';

            // Client Details
            echo '<div class="section"><h3>Client Details</h3>';
            echo '<p><span class="field-label">Client ID:</span><span class="field-value">' . htmlspecialchars($row['client_id']) . '</span></p>';
            echo '<p><span class="field-label">Client Name:</span><span class="field-value">' . htmlspecialchars($row['client_name']) . '</span></p>';
            echo '<p><span class="field-label">Insurance Plan:</span><span class="field-value">' . htmlspecialchars($row['insurance_plan']) . '</span></p>';
            echo '<p><span class="field-label">Primary Email:</span><span class="field-value">' . htmlspecialchars($row['client_email']) . '</span></p>';
            echo '<p><span class="field-label">Other Emails:</span><span class="field-value">' . htmlspecialchars($row['other_emails']) . '</span></p>';
            echo '<p><span class="field-label">Phone Number:</span><span class="field-value">' . htmlspecialchars($row['client_phone_number']) . '</span></p>';
            echo '<p><span class="field-label">Other Phone Numbers:</span><span class="field-value">' . htmlspecialchars($row['other_phone_numbers']) . '</span></p>';
            echo '<p><span class="field-label">Street Address:</span><span class="field-value">' . htmlspecialchars($row['street_address']) . '</span></p>';
            echo '<p><span class="field-label">City:</span><span class="field-value">' . htmlspecialchars($row['city']) . '</span></p>';
            echo '<p><span class="field-label">Zip Code:</span><span class="field-value">' . htmlspecialchars($row['zip_code']) . '</span></p>';
            echo '<p><span class="field-label">State:</span><span class="field-value">' . htmlspecialchars($row['state']) . '</span></p>';
            echo '<p><span class="field-label">Country:</span><span class="field-value">' . htmlspecialchars($row['country']) . '</span></p>';
            echo '<p><span class="field-label">Client Notes:</span><span class="field-value">' . nl2br(htmlspecialchars($row['client_notes'])) . '</span></p>';
            echo '<p><span class="field-label">Previous Gig History:</span><span class="field-value">' . htmlspecialchars($row['previous_gig_history']) . '</span></p>';
            echo '<p><span class="field-label">Appliances Owned:</span><span class="field-value">' . htmlspecialchars($row['appliances_owned']) . '</span></p>';
            echo '<p><span class="field-label">Maintenance Plan:</span><span class="field-value">' . htmlspecialchars($row['maintenance_plan']) . '</span></p>';
            echo '<p><span class="field-label">Payee ID:</span><span class="field-value">' . htmlspecialchars($row['payee_id']) . '</span></p>';
            echo '<p><span class="field-label">Extra Field 1:</span><span class="field-value">' . htmlspecialchars($row['client_extra_field1']) . '</span></p>';
            echo '<p><span class="field-label">Extra Field 2:</span><span class="field-value">' . htmlspecialchars($row['client_extra_field2']) . '</span></p>';
            echo '</div>';

            // Payee Details (if available)
            if (!empty($row['payee_id'])) {
                echo '<div class="section"><h3>Payee Details</h3>';
                echo '<p><span class="field-label">Payee ID:</span><span class="field-value">' . htmlspecialchars($row['payee_id']) . '</span></p>';
                echo '<p><span class="field-label">Payee Name:</span><span class="field-value">' . htmlspecialchars($row['payee_name']) . '</span></p>';
                echo '<p><span class="field-label">Payee Email:</span><span class="field-value">' . htmlspecialchars($row['payee_email']) . '</span></p>';
                echo '<p><span class="field-label">Other Payee Emails:</span><span class="field-value">' . htmlspecialchars($row['payee_other_emails']) . '</span></p>';
                echo '<p><span class="field-label">Payee Phone Number:</span><span class="field-value">' . htmlspecialchars($row['payee_phone_number']) . '</span></p>';
                echo '<p><span class="field-label">Other Payee Phone Numbers:</span><span class="field-value">' . htmlspecialchars($row['payee_other_phone_numbers']) . '</span></p>';
                echo '<p><span class="field-label">Payee Notes:</span><span class="field-value">' . nl2br(htmlspecialchars($row['payee_notes'])) . '</span></p>';
                echo '<p><span class="field-label">Payee Relation:</span><span class="field-value">' . htmlspecialchars($row['payee_relation']) . '</span></p>';
                echo '<p><span class="field-label">Extra Field 1:</span><span class="field-value">' . htmlspecialchars($row['payee_extra_field1']) . '</span></p>';
                echo '<p><span class="field-label">Extra Field 2:</span><span class="field-value">' . htmlspecialchars($row['payee_extra_field2']) . '</span></p>';
                echo '</div>';
            } else {
                echo '<div class="section"><h3>Payee Details</h3>';
                echo '<p>No Payee Information Available.</p>';
                echo '</div>';
            }

            // Technician (User) Details
            echo '<div class="section"><h3>Technician Details</h3>';
            echo '<p><span class="field-label">Tech ID:</span><span class="field-value">' . htmlspecialchars($row['tech_id']) . '</span></p>';
            echo '<p><span class="field-label">Role ID:</span><span class="field-value">' . htmlspecialchars($row['role_id']) . '</span></p>';
            echo '<p><span class="field-label">Name:</span><span class="field-value">' . htmlspecialchars($row['tech_name']) . '</span></p>';
            echo '<p><span class="field-label">Email:</span><span class="field-value">' . htmlspecialchars($row['tech_email']) . '</span></p>';
            echo '<p><span class="field-label">Phone Number:</span><span class="field-value">' . htmlspecialchars($row['tech_phone_number']) . '</span></p>';
            echo '</div>';

            echo '</div>'; // End of gig-container
        }

        echo '</body>
</html>';

        // Free result set
        $result->free();
    } else {
        echo "No gigs found in the database.";
    }
} else {
    // Query error
    echo "Error: " . $dbConn->error;
}

// Close the database connection
$dbConn->close();

//QB Invoice number
//Child_Of Gig (Follow Up)
//Client Notes is JSON to seperate gate code
?>

