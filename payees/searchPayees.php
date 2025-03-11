<?php
// **Remove or comment out these lines in production**
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Set the content type to JSON
header('Content-Type: application/json');

// Include the database configuration file
include '../../config.php'; // Ensure this path is correct

$response = [];

try {
    if (isset($_GET['query'])) {
        $query = trim($_GET['query']);

        if (strlen($query) < 2) {
            // Return an empty array if query is too short
            echo json_encode([]);
            exit;
        }

        // Prepare the SQL statement with LIKE for fuzzy search
        $stmt = $dbConn->prepare("
            SELECT payee_id, payee_name, payee_last_name
            FROM payees
            WHERE payee_name LIKE CONCAT('%', ?, '%')
               OR payee_last_name LIKE CONCAT('%', ?, '%')
            LIMIT 10
        ");

        if ($stmt === false) {
            throw new Exception("Prepare failed: " . htmlspecialchars($dbConn->error));
        }

        // Bind the query parameter to both payee_name and payee_last_name
        $stmt->bind_param('ss', $query, $query);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . htmlspecialchars($stmt->error));
        }

        $result = $stmt->get_result();

        $payees = [];
        while ($row = $result->fetch_assoc()) {
            $payees[] = [
                'payee_id' => $row['payee_id'],
                'payee_name' => $row['payee_name'],
                'payee_last_name' => $row['payee_last_name']
            ];
        }

        echo json_encode($payees);
        $stmt->close();
    } else {
        // If 'query' parameter is not set, return an empty array
        echo json_encode([]);
    }
} catch (Exception $e) {
    // Return the error message as JSON
    http_response_code(500); // Set HTTP status code to 500
    $response['error'] = $e->getMessage();
    echo json_encode($response);
}
?>
