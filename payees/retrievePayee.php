<?php
include '../../config.php';

// Ensure the connection is established
if (!$dbConn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Define the SQL query to select all records from the payees table
$sql = "SELECT payee_id, payee_name, email, other_emails, phone_number, other_phone_numbers, payee_notes, payee_relation, extra_field1, extra_field2 FROM payees";

// Execute the query
$result = $dbConn->query($sql);

// Check if the query was successful
if ($result) {
    if ($result->num_rows > 0) {
        // Start the HTML table and define table headers
        echo "<table border='1' cellpadding='10' cellspacing='0'>";
        echo "<tr>
                <th>Payee ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Other Emails</th>
                <th>Phone Number</th>
                <th>Other Phone Numbers</th>
                <th>Notes</th>
                <th>Relation</th>
                <th>Extra Field 1</th>
                <th>Extra Field 2</th>
              </tr>";

        // Fetch and display each row of data
        while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . htmlspecialchars($row["payee_id"]) . "</td>
                    <td>" . htmlspecialchars($row["payee_name"]) . "</td>
                    <td>" . htmlspecialchars($row["email"]) . "</td>
                    <td>" . htmlspecialchars($row["other_emails"]) . "</td>
                    <td>" . htmlspecialchars($row["phone_number"]) . "</td>
                    <td>" . htmlspecialchars($row["other_phone_numbers"]) . "</td>
                    <td>" . nl2br(htmlspecialchars($row["payee_notes"])) . "</td>
                    <td>" . htmlspecialchars($row["payee_relation"]) . "</td>
                    <td>" . htmlspecialchars($row["extra_field1"]) . "</td>
                    <td>" . htmlspecialchars($row["extra_field2"]) . "</td>
                  </tr>";
        }

        // Close the HTML table
        echo "</table>";
    } else {
        echo "No records found in the payees table.";
    }

    // Free result set
    $result->free();
} else {
    // Handle query error
    echo "Error retrieving data: " . $dbConn->error;
}

// Optionally, close the database connection
// $dbConn->close();
?>
