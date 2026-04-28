<?php
// 1. Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "printpro_db"; // Make sure this matches your actual DB name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Connection failed"]));
}

// 2. Catch the raw JSON stream from your JavaScript
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

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

    if ($conn->query($sql) === TRUE) {
        // Send a success response back to the JavaScript
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => $conn->error]);
    }
}

$conn->close();
?>