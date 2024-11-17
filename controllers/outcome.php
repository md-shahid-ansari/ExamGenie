<?php

$servername = "localhost:3306";
$username = "root";
$password = "Sadaf2003@";
$dbname = "genie_db";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create the 'outcomes' table if it does not exist, including 'course_code' field
$tableCreationQuery = "
    CREATE TABLE IF NOT EXISTS outcomes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        outcome_text TEXT NOT NULL,
        course_code VARCHAR(100) NOT NULL  -- New column for course code
    );
";

if (!mysqli_query($conn, $tableCreationQuery)) {
    die("Error creating table: " . mysqli_error($conn));
}

// Helper function to send JSON responses
function sendResponse($data = [], $statusCode = 200, $message = '') {
    header("Content-Type: application/json");
    http_response_code($statusCode);
    echo json_encode(['message' => $message, 'data' => $data]);
    exit;
}

// Function to fetch outcomes from the database
function fetchOutcomes($course_code = null) {
    global $conn;
    $query = "SELECT * FROM outcomes";  // Fetch all outcomes by default

    // If a course_code is specified, filter outcomes by course_code
    if ($course_code) {
        $query .= " WHERE course_code = '$course_code'";  // Adjust query to include filtering by course_code
    }

    $result = mysqli_query($conn, $query);
    $outcomes = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $outcomes[] = $row;
        }
    }
    return ['outcomes' => $outcomes];
}

// Handle incoming requests based on HTTP method
$requestMethod = $_SERVER['REQUEST_METHOD'];

switch ($requestMethod) {
    case 'GET':
        // Fetch the list of outcomes from DB with optional course_code filter
        $urlParams = $_GET;
        $course_code = isset($urlParams['course_code']) ? $urlParams['course_code'] : null;
        $outcomes = fetchOutcomes($course_code);  // Pass the course_code if available
        sendResponse($outcomes, 200, 'Outcomes fetched successfully');
        break;

    case 'POST':
        // Read and decode JSON input
        $inputData = json_decode(file_get_contents("php://input"), true);

        if (!isset($inputData['outcome_text'], $inputData['course_code']) ||
            empty($inputData['outcome_text']) || empty($inputData['course_code'])) {
            sendResponse([], 400, 'Invalid or missing input fields');
        }

        $outcome_text = $inputData['outcome_text'];
        $course_code = $inputData['course_code'];
        
        $query = "INSERT INTO outcomes (outcome_text, course_code) VALUES ('$outcome_text', '$course_code')";
        if (mysqli_query($conn, $query)) {
            $outcomes = fetchOutcomes();
            sendResponse($outcomes, 201, 'Outcome added successfully');
        } else {
            sendResponse([], 500, 'Failed to add outcome');
        }
        break;

    case 'PUT':
        // Read and decode JSON input
        $inputData = json_decode(file_get_contents("php://input"), true);

        if (!isset($inputData['id'], $inputData['outcome_text'], $inputData['course_code']) ||
            empty($inputData['outcome_text']) || empty($inputData['course_code'])) {
            sendResponse([], 400, 'Invalid or missing input fields');
        }

        $id = (int)$inputData['id'];
        $outcome_text = $inputData['outcome_text'];
        $course_code = $inputData['course_code'];

        $query = "UPDATE outcomes SET outcome_text = '$outcome_text', course_code = '$course_code' WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $outcomes = fetchOutcomes();
            sendResponse($outcomes, 200, 'Outcome updated successfully');
        } else {
            sendResponse([], 500, 'Failed to update outcome');
        }
        break;

    case 'DELETE':
        // Read and decode JSON input
        $inputData = json_decode(file_get_contents("php://input"), true);

        if (!isset($inputData['id']) || !is_numeric($inputData['id'])) {
            sendResponse([], 400, 'Invalid or missing input fields');
        }

        $id = (int)$inputData['id'];
        $query = "DELETE FROM outcomes WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $outcomes = fetchOutcomes();
            sendResponse($outcomes, 200, 'Outcome deleted successfully');
        } else {
            sendResponse([], 500, 'Failed to delete outcome');
        }
        break;

    default:
        sendResponse([], 405, 'Method not allowed');
}
?>