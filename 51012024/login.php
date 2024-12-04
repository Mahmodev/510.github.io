<?php
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_email'] = $user['email']; // Store user-specific information
$_SESSION['redirect_page'] = $user['redirect_page']; // Store the redirect page securely


date_default_timezone_set("Africa/Cairo");

// Mock database
$users = [
    "user@example.com" => [
        "password" => password_hash("password123", PASSWORD_BCRYPT),
        "access_deadline" => "2024-12-30 23:59:59",
        // "allowed_country" => "EG",
        "redirect_page" => "Modal-3.php" // Page to redirect upon successful login
    ],

    "user2@example.com" => [
         "password" => password_hash("password123", PASSWORD_BCRYPT),
        "access_deadline" => "2024-11-30 23:59:59",
        // "allowed_country" => "EG",
        "redirect_page" => "QuestionBank.html" // Page to redirect upon successful login
    ]
];

// Get client IP and determine location
function get_client_country() {
    $client_ip = $_SERVER['REMOTE_ADDR'];
    $response = @file_get_contents("http://ipinfo.io/{$client_ip}/json");
    if ($response) {
        $data = json_decode($response, true);
        return $data['country'] ?? null;
    }
    return null;
}

function sign_url($url) {
    $secret_key = "YOUMABASSELNOUR"; // Replace with a strong secret key
    return hash_hmac('sha256', $url, $secret_key);
}

function encrypt_url($url) {
    $encrypted_url = base64_encode($url);
    $signature = sign_url($encrypted_url);
    return $encrypted_url . ':' . $signature;
}


// Read and parse JSON request
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);
$email = $data['email'] ?? null;
$password = $data['password'] ?? null;

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["error" => "Email and password are required"]);
    exit;
}

// Verify user
if (!isset($users[$email])) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid credentials"]);
    exit;
}

$user = $users[$email];

// Verify password
if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid credentials"]);
    exit;
}

// Check access deadline
$egypt_time = new DateTime("now", new DateTimeZone("Africa/Cairo"));
$access_deadline = new DateTime($user['access_deadline']);
if ($egypt_time > $access_deadline) {
    http_response_code(403);
    echo json_encode(["error" => "Access expired"]);
    exit;
}

// Verify country
$client_country = get_client_country();
if ($client_country !== $user['allowed_country']) {
    http_response_code(403);
    echo json_encode(["error" => "Access denied from this location"]);
    exit;
}

// Encrypt the redirect URL
$redirect_page = $user['redirect_page'];
$encrypted_url = encrypt_url($redirect_page);

// Success response
echo json_encode([
    "message" => "Login successful!",
    "redirect_url" => "redirect.php?url={$encrypted_url}"
]);

?>
