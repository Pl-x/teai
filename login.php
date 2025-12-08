<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'backend/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $errors[] = "Both email and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT user_id, name, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                header("Location: dashboard.php");
                exit();
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "Email not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Smart Farmer</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: "Segoe UI", Arial, sans-serif;
    height: 100vh;
    overflow: hidden;
    position: relative;
    background: linear-gradient(135deg, #1e4d2b 0%, #2d5a3d 25%, #3a6b4c 50%, #4a7c5d 75%, #5a8d6e 100%);
}

.background-animation {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 0;
}

.leaf {
    position: absolute;
    width: 60px;
    height: 30px;
    background: radial-gradient(ellipse at center, rgba(76, 175, 80, 0.3) 0%, rgba(56, 142, 60, 0.2) 50%, transparent 100%);
    border-radius: 50% 0 50% 0;
    animation: fall linear infinite;
    opacity: 0;
    filter: blur(1px);
}

.leaf:nth-child(1) { left: 10%; animation-duration: 15s; animation-delay: 0s; }
.leaf:nth-child(2) { left: 25%; animation-duration: 18s; animation-delay: 2s; width: 45px; }
.leaf:nth-child(3) { left: 40%; animation-duration: 20s; animation-delay: 4s; }
.leaf:nth-child(4) { left: 55%; animation-duration: 16s; animation-delay: 1s; width: 50px; }
.leaf:nth-child(5) { left: 70%; animation-duration: 19s; animation-delay: 3s; }
.leaf:nth-child(6) { left: 85%; animation-duration: 17s; animation-delay: 5s; width: 55px; }
.leaf:nth-child(7) { left: 15%; animation-duration: 21s; animation-delay: 6s; }
.leaf:nth-child(8) { left: 60%; animation-duration: 14s; animation-delay: 2.5s; }

@keyframes fall {
    0% {
        top: -10%;
        opacity: 0;
        transform: translateX(0) rotate(0deg);
    }
    10% {
        opacity: 0.6;
    }
    90% {
        opacity: 0.3;
    }
    100% {
        top: 110%;
        opacity: 0;
        transform: translateX(100px) rotate(360deg);
    }
}

.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(ellipse at top, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.6) 100%);
    backdrop-filter: blur(2px);
    z-index: 1;
}

.container {
    position: relative;
    z-index: 2;
    width: 420px;
    margin: 0 auto;
    padding: 45px 40px;
    background: rgba(255, 255, 255, 0.12);
    border-radius: 24px;
    backdrop-filter: blur(20px) saturate(180%);
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.2),
        0 0 0 1px rgba(255, 255, 255, 0.1);
    color: white;
    top: 50%;
    transform: translateY(-50%);
    animation: slideIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-40%) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(-50%) scale(1);
    }
}

.logo-area {
    text-align: center;
    margin-bottom: 35px;
}

.tea-icon {
    font-size: 56px;
    margin-bottom: 10px;
    display: inline-block;
    animation: float 3s ease-in-out infinite;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

h2 {
    text-align: center;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 32px;
    letter-spacing: -0.5px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.subtitle {
    text-align: center;
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 30px;
    font-weight: 300;
}

.input-group {
    position: relative;
    margin-bottom: 25px;
}

.input-group label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 8px;
    color: rgba(255, 255, 255, 0.95);
    letter-spacing: 0.3px;
}

.input-group input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    outline: none;
    background: rgba(255, 255, 255, 0.15);
    color: white;
    font-size: 15px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
}

.input-group input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.input-group input:focus {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(139, 195, 74, 0.8);
    box-shadow: 0 0 0 4px rgba(139, 195, 74, 0.15), 0 4px 12px rgba(0,0,0,0.2);
    transform: translateY(-2px);
}

.input-group input:hover {
    border-color: rgba(255, 255, 255, 0.35);
}

button {
    width: 100%;
    padding: 16px;
    border: none;
    background: linear-gradient(135deg, #66bb6a 0%, #43a047 100%);
    color: white;
    font-size: 16px;
    font-weight: 600;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
    position: relative;
    overflow: hidden;
    margin-top: 10px;
}

button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

button:hover::before {
    left: 100%;
}

button:hover {
    background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.5);
    transform: translateY(-2px);
}

button:active {
    transform: translateY(0);
    box-shadow: 0 2px 10px rgba(76, 175, 80, 0.4);
}

.error {
    background: rgba(244, 67, 54, 0.2);
    border: 1px solid rgba(244, 67, 54, 0.5);
    color: #ffcdd2;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
    backdrop-filter: blur(10px);
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.success {
    background: rgba(76, 175, 80, 0.2);
    border: 1px solid rgba(76, 175, 80, 0.5);
    color: #c8e6c9;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
    font-size: 14px;
    backdrop-filter: blur(10px);
    animation: fadeIn 0.5s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.register-btn {
    margin-top: 25px;
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
}

.register-btn p {
    color: rgba(255, 255, 255, 0.85);
    font-size: 14px;
    font-weight: 400;
}

.register-btn a {
    color: #81c784;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    position: relative;
}

.register-btn a::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: #81c784;
    transition: width 0.3s;
}

.register-btn a:hover::after {
    width: 100%;
}

.register-btn a:hover {
    color: #a5d6a7;
    text-shadow: 0 0 10px rgba(129, 199, 132, 0.5);
}

@media (max-width: 480px) {
    .container {
        width: 95%;
        padding: 35px 25px;
        margin: 20px auto;
        top: 20px;
        transform: translateY(0);
    }
    
    h2 {
        font-size: 28px;
    }
}
</style>
</head>
<body>

<div class="background-animation">
    <div class="leaf"></div>
    <div class="leaf"></div>
    <div class="leaf"></div>
    <div class="leaf"></div>
    <div class="leaf"></div>
    <div class="leaf"></div>
    <div class="leaf"></div>
    <div class="leaf"></div>
</div>

<div class="overlay"></div>

<div class="container">
    <div class="logo-area">
        <div class="tea-icon">🍃</div>
        <h2>Welcome Back</h2>
        <div class="subtitle">Smart Farmer</div>
    </div>

    <?php
    if (!empty($errors)) {
        echo '<div class="error">'.implode('<br>', $errors).'</div>';
    }

    if (isset($_SESSION['success_message'])) {
        echo '<div class="success">'.$_SESSION['success_message'].'</div>';
        unset($_SESSION['success_message']);
    }
    ?>

    <form method="post" action="">
        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="your@email.com" required>
        </div>
        
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
        </div>
        
        <button type="submit">Sign In</button>
    </form>

    <div class="register-btn">
        <p>Don't have an account? <a href="register.php">Create one now</a></p>
    </div>
</div>

</body>
</html>