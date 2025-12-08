<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'backend/db.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$name = $_SESSION['name'] ?? "User";
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Tea Leaf AI Dashboard</title>

<style>
:root {
    --bg: #f3f5f7;
    --card: #ffffff;
    --text: #000000;
    --primary: #1b4332;
    --primary-light: #2d6a4f;
    --success-bg: #e9f7ef;
}

.dark {
    --bg: #121212;
    --card: #1f1f1f;
    --text: #ffffff;
    --primary: #0b3a2b;
    --primary-light: #1e6b4f;
    --success-bg: #163c33;
}

body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background: var(--bg);
    display: flex;
    height: 100vh;
    overflow: hidden;
    transition: 0.3s ease;
}

.sidebar {
    width: 260px;
    background: var(--primary);
    color: white;
    padding: 20px;
    display: flex;
    flex-direction: column;
    transition: .3s;
}

.sidebar a, .sidebar button {
    padding: 12px;
    margin: 8px 0;
    text-decoration: none;
    background: rgba(255,255,255,0.12);
    display: block;
    border-radius: 8px;
    color: white;
    border: none;
    cursor: pointer;
}

.sidebar a:hover, .sidebar button:hover {
    background: var(--primary-light);
}

.main {
    flex: 1;
    padding: 25px;
    overflow-y: auto;
    color: var(--text);
}

.header {
    background: var(--card);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.upload-box {
    background: var(--card);
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0px 3px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

#preview {
    display: none;
    max-width: 350px;
    margin-top: 15px;
    border-radius: 12px;
}

#camera {
    max-width: 350px;
    border-radius: 12px;
}

.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--primary-light);
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    display: none;
    z-index: 9999;
}

.results {
    background: var(--card);
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0px 3px 15px rgba(0,0,0,0.1);
}

.rating-box {
    margin-top: 20px;
    padding: 20px;
    background: var(--card);
    border-radius: 12px;
    box-shadow: 0px 3px 15px rgba(0,0,0,0.08);
}

.stars span {
    font-size: 35px;
    cursor: pointer;
    color: #ccc;
    transition: .2s;
}

.stars span.active,
.stars span:hover,
.stars span:hover ~ span {
    color: gold;
}

@media(max-width: 780px){
    .sidebar { display: none; }
}
</style>
</head>

<body id="body">

<!-- Toast -->
<div class="toast" id="toast">Message...</div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <h2>🍃 Tea AI</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="history.php">Prediction History</a>
    <a href="profile.php">Profile</a>
    <button onclick="toggleDarkMode()">🌙 Dark/Light Mode</button>
    <a href="login.php" style="margin-top:auto; background:#c00000;" onclick="logout()">Logout</a>
</div>

<!-- Main -->
<div class="main">

    <!-- Header -->
    <div class="header">
        <h2>Welcome, <?= htmlspecialchars($name); ?> 👋</h2>
        <button onclick="toggleSidebar()">☰ Menu</button>
    </div>

    <!-- Upload Box -->
    <div class="upload-box">
        <h3>🔍 Upload or Capture Tea Leaf Image</h3>

        <form action="predict.php" method="POST" enctype="multipart/form-data" onsubmit="showLoader()">

            <label><strong>Select Image</strong></label>
            <input type="file" name="leaf_image" accept="image/*" capture="environment" onchange="previewImage(event)" required>

            <img id="preview">

            <hr style="margin: 25px 0;">

            <h4>📷 Or Take a Photo</h4>

            <video id="camera" autoplay playsinline></video>
            <br>
            <button type="button" onclick="takePhoto()">Capture Photo</button>

            <canvas id="snapshot" style="display:none;"></canvas>
            <input type="hidden" name="leaf_image_cam" id="leaf_image_cam">

            <p id="loader" style="display:none; margin-top:10px; font-weight:bold; color:var(--primary-light);">
                ⏳ Analyzing... Please wait
            </p>

            <button type="submit" style="background: var(--primary-light); margin-top:20px;">Analyze Image</button>
        </form>
    </div>

    <!-- Error Message -->
    <?php if(isset($_SESSION['analysis_error'])): ?>
        <div class="results" style="border-left: 5px solid #c00000; background: #ffe6e6;">
            <h3>⚠️ Analysis Error</h3>
            <p style="color: #c00000;"><?= htmlspecialchars($_SESSION['analysis_error']); ?></p>
        </div>
        <?php unset($_SESSION['analysis_error']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['analysis'])): ?>
        <?php $r = $_SESSION['analysis']; ?>

        <div class="results">
            <h3>✅ AI Diagnosis Result</h3>
            <p><strong>Disease:</strong> <?= htmlspecialchars($r['disease']); ?></p>
            <p><strong>Confidence:</strong> <span style="color: var(--primary-light); font-weight: bold;"><?= $r['confidence']; ?>%</span></p>
            <?php if (!empty($r['visualization_path'])): ?>
        <div style="margin: 20px 0; border: 1px solid #e0e0e0; border-radius: 12px; padding: 10px; text-align: center; background: #fff;">
            <h4 style="margin: 0 0 10px 0; color: #555;">Detailed Analysis</h4>
            <img src="<?= htmlspecialchars($r['visualization_path']); ?>" 
                 alt="Prediction Visualization" 
                 style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        </div>
        <?php endif; ?>
            <div style="padding:15px; background:var(--success-bg); border-left:5px solid var(--primary-light); border-radius:10px;">
                <strong>💡 Recommended Treatment:</strong>
                <p><?= htmlspecialchars($r['solution']); ?></p>
            </div>
        </div>

        <!-- Rating Section -->
        <div class="rating-box">
            <h3>⭐ Rate This Diagnosis</h3>

            <form action="rate.php" method="POST">
                <input type="hidden" name="analysis_id" value="<?= $r['id']; ?>">
                <input type="hidden" id="rating_value" name="rating" value="0">

                <div class="stars" id="stars">
                    <span data-star="1">★</span>
                    <span data-star="2">★</span>
                    <span data-star="3">★</span>
                    <span data-star="4">★</span>
                    <span data-star="5">★</span>
                </div>

                <button type="submit" style="margin-top:15px; background:var(--primary-light);">Submit Rating</button>
            </form>
        </div>

        <?php unset($_SESSION['analysis']); ?>
    <?php endif; ?>

</div>

<script>
// Sidebar toggle
function toggleSidebar(){
    const s = document.getElementById("sidebar");
    s.style.display = s.style.display === "none" ? "block" : "none";
}

// Toast
function showToast(msg){
    const t = document.getElementById("toast");
    t.innerText = msg;
    t.style.display = "block";
    setTimeout(()=> t.style.display = "none", 3000);
}

// Dark mode
function toggleDarkMode(){
    document.getElementById("body").classList.toggle("dark");
}

// Image preview
function previewImage(e){
    const img = document.getElementById("preview");
    img.src = URL.createObjectURL(e.target.files[0]);
    img.style.display = "block";
    showToast("Image loaded!");
}

// Loader
function showLoader(){
    document.getElementById("loader").style.display = "block";
}

// Camera init
const video = document.getElementById("camera");
navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
.then(stream => video.srcObject = stream)
.catch(err => console.log("Camera unavailable:", err));

// Take photo
function takePhoto(){
    let canvas = document.getElementById("snapshot");
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext("2d").drawImage(video, 0, 0);

    document.getElementById("leaf_image_cam").value =
        canvas.toDataURL("image/png");

    showToast("Photo captured!");
}

document.querySelectorAll(".stars span").forEach(star => {
    star.addEventListener("click", function(){
        const rating = this.getAttribute("data-star");
        document.getElementById("rating_value").value = rating;

        // highlight stars
        document.querySelectorAll(".stars span").forEach(s => {
            s.classList.remove("active");
        });
        for(let i=0; i < rating; i++){
            document.querySelectorAll(".stars span")[i].classList.add("active");
        }
    });
});
function logout() {
    window.location.replace('logout.php');
}
</script>

</body>
</html>

