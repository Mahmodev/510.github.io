<?php
date_default_timezone_set("Africa/Cairo");

// File to store user data
$file_path = 'users.json';

// Read input data
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

if (!$data) {
    error_log("Failed to decode JSON input: " . $request_body); // Debugging
    http_response_code(400);
    echo json_encode(["error" => "Invalid request format"]);
    exit;
}

// Validate required fields
$required_fields = ['first_name', 'last_name', 'email', 'password', 'country', 'whatsapp'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        error_log("Missing field: $field"); // Debugging
        http_response_code(400);
        echo json_encode(["error" => "Missing field: $field"]);
        exit;
    }
}

// Read existing users
$users = [];
if (file_exists($file_path)) {
    $users = json_decode(file_get_contents($file_path), true);
    if (!$users) {
        error_log("Failed to read or decode users.json"); // Debugging
        $users = [];
    }
}

// Check for duplicate email
if (isset($users[$data['email']])) {
    error_log("Duplicate email: " . $data['email']); // Debugging
    http_response_code(400);
    echo json_encode(["error" => "User with this email already exists"]);
    exit;
} elseif (isset($users[$data['whatsapp']])) {
    error_log("Duplicate whatsapp: " . $data['whatsapp']); // Debugging
    http_response_code(400);
    echo json_encode(["error" => "User with this phone number already exists"]);
    exit;
}

// Hash password
$data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

// Add user to the list
$users[$data['email']] = $data;

// Save users back to the file
if (file_put_contents($file_path, json_encode($users, JSON_PRETTY_PRINT))) {
    echo json_encode(["message" => "Registration successful!"]);
} else {
    error_log("Failed to write to users.json"); // Debugging
    http_response_code(500);
    echo json_encode(["error" => "Failed to save user data"]);
}
error_log(print_r($data, true)); // Log received data to the server's error log

?>

