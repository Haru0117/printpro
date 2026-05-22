<?php
// Database Configuration — AwardSpace Production
$servername = "fdb1034.awardspace.net";
$username   = "4728062_printpro";
$password   = "iF8q#5:*9o/iqF!4";
$dbname     = "4728062_printpro";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

// 2. Catch the raw JSON stream from your JavaScript
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

$result = null;
if ($data) {
    // 3. Map the data to variables
    $projectName = $conn->real_escape_string($data['projectName']);
    $category = $conn->real_escape_string($data['category']);
    $quantity = (int) $data['quantity'];
    $totalPrice = $conn->real_escape_string($data['totalPrice']);

    // 4. Prepare your SQL Insert statement
    // Ensure your table name and column names match your database!
    $sql = "INSERT INTO orders (project_name, category, quantity, total_price) 
            VALUES ('$projectName', '$category', $quantity, '$totalPrice')";

    $result = $conn->query($sql);
}

// CLOSE CONNECTION IMMEDIATELY after fetching data (Fetch-Close-Render pattern)
$conn->close();
$conn = null;

// Now perform rendering with the data
if ($result === TRUE) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => $conn->error ?? "Query failed"]);
}
?>