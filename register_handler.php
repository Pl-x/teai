<?php
session_start();
require_once 'backend/db.php'; // Make sure this connects properly to your DB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF token check
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        die("Invalid CSRF token");
    }

    // Collect and sanitize input
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $country = trim($_POST['country']);
    $language = trim($_POST['language']);

    $errors = [];

    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($country) || empty($language)) {
        $errors[] = "All fields are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn()) {
        $errors[] = "Email already registered.";
    }

    // If there are errors, redirect back to register.php
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        header("Location: register.php");
        exit();
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Generate UUID
    function generate_uuid_v4(){
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    $uuid = generate_uuid_v4();

    // Insert user into database
    $stmt = $pdo->prepare("INSERT INTO users (uuid, name, email, password_hash, country, language) VALUES (?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([$uuid, $name, $email, $password_hash, $country, $language])) {
        // Success: redirect to login page
        $_SESSION['success_message'] = "Registration successful! Please log in.";
        header("Location: login.php");
        exit();
    } else {
        die("Error registering user: " . implode(", ", $stmt->errorInfo()));
    }
}
?>




