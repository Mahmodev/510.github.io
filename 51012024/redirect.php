<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Access denied: You must log in first.");
}

// Retrieve the secure redirect page
$redirect_page = $_SESSION['redirect_page'];


if (!isset($_GET['url'])) {
    die("Invalid request");
}

// Decrypt the URL
$encrypted_url = $_GET['url'];
$redirect_page = base64_decode($encrypted_url);

function verify_url($encrypted_url, $provided_signature) {
    $secret_key = "YOUMABASSELNOUR"; // Use the same key as in login.php
    $calculated_signature = hash_hmac('sha256', $encrypted_url, $secret_key);
    return hash_equals($calculated_signature, $provided_signature);
}

if (!isset($_GET['url'])) {
    die("Invalid request");
}

$url_parts = explode(':', $_GET['url']);
if (count($url_parts) !== 2) {
    die("Invalid URL format");
}

list($encrypted_url, $signature) = $url_parts;

// Verify the signature
if (!verify_url($encrypted_url, $signature)) {
    die("Invalid URL signature");
}

// Decrypt the URL
$redirect_page = base64_decode($encrypted_url);

// Verify the decrypted URL (ensure it's an expected path)
$allowed_pages = ["Modal-3.php", "profile.php"]; // Define allowed pages
if (!in_array($redirect_page, $allowed_pages)) {
    die("Unauthorized redirection attempt");
}

// Redirect to the verified page
header("Location: $redirect_page");
exit;

?>
