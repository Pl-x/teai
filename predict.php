<?php
session_start();
require_once 'backend/db.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo "Not authenticated";
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$logFile = __DIR__ . '/log/prediction_log.txt';

$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
// Ensure log directory exists
if (!is_dir(__DIR__ . '/log')) {
    mkdir(__DIR__ . '/log', 0755, true);
}

$filename = time() . '_' . bin2hex(random_bytes(6)) . '.png';
$targetPath = $uploadDir . '/' . $filename;
$saved = false;

// Handle normal file upload
if (!empty($_FILES['leaf_image']) && $_FILES['leaf_image']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['leaf_image']['tmp_name'];
    if (move_uploaded_file($tmp, $targetPath)) {
        $saved = true;
    }
}

// Handle camera base64 input
if (!$saved && !empty($_POST['leaf_image_cam'])) {
    $data = $_POST['leaf_image_cam'];
    if (preg_match('/^data:image\/(\w+);base64,/', $data, $m)) {
        $data = preg_replace('/^data:image\/\w+;base64,/', '', $data);
        $decoded = base64_decode($data);
        if ($decoded !== false && file_put_contents($targetPath, $decoded) !== false) {
            $saved = true;
        }
    }
}

if (!$saved) {
    $_SESSION['analysis_error'] = "Failed to upload or save the image.";
    header('Location: dashboard.php');
    exit;
}

// ===============================================
// Python Execution Logic
// ===============================================
$python = __DIR__ . '/model/.venv/bin/python3';
$cli = __DIR__ . '/model/predict_cli.py';
$cmd = escapeshellcmd($python) . ' ' . escapeshellarg($cli) . ' ' . escapeshellarg($targetPath) . ' 2>&1';

exec($cmd, $outLines, $ret);

// Log everything
$rawOutput = implode("\n", $outLines);
file_put_contents($logFile, "=== NEW PREDICTION ===\n", FILE_APPEND);
file_put_contents($logFile, "Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents($logFile, "Command: $cmd\n", FILE_APPEND);
file_put_contents($logFile, "Exit code: $ret\n", FILE_APPEND);
file_put_contents($logFile, "Raw Output:\n$rawOutput\n", FILE_APPEND);

// Check exit code
if ($ret !== 0) {
    $_SESSION['analysis_error'] = "Python script failed (exit code: $ret). Check logs.";
    file_put_contents($logFile, "ERROR: Non-zero exit code\n\n", FILE_APPEND);
    header('Location: dashboard.php');
    exit;
}

// Find valid JSON line, skipping warnings
$result = null;
foreach ($outLines as $line) {
    // Skip known warning patterns
    if (strpos($line, 'QFont::') !== false ||
        strpos($line, 'QXcb') !== false ||
        empty(trim($line))) {
        continue;
        }

        $decoded = json_decode($line, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $result = $decoded;
        file_put_contents($logFile, "Valid JSON found: $line\n\n", FILE_APPEND);
        break;
    }
}

if ($result === null) {
    $_SESSION['analysis_error'] = "No valid prediction output. Check logs.";
    file_put_contents($logFile, "ERROR: No valid JSON in output\n\n", FILE_APPEND);
    header('Location: dashboard.php');
    exit;
}

// Check for errors in result
if (isset($result['error'])) {
    $_SESSION['analysis_error'] = "Prediction error: " . $result['error'];
    file_put_contents($logFile, "ERROR from Python: " . $result['error'] . "\n\n", FILE_APPEND);
    header('Location: dashboard.php');
    exit;
}

// Prepare values to store
$disease = $result['disease'] ?? 'Unknown';
$confidence = isset($result['confidence']) ? (float)$result['confidence'] : 0.0;
$probabilities = isset($result['probabilities']) ? json_encode($result['probabilities']) : json_encode([]);
$info = $result['info'] ?? [];
$solution = $info['treatment'] ?? ($result['solution'] ?? 'No solution available');
$visualization_path = $result['visualization_path'] ?? null;

try {
    // Attempt to insert with the new 'visualization_path' column
    $stmt = $pdo->prepare("INSERT INTO predictions (user_id, image_path, disease, confidence, probabilities, solution, visualization_path) VALUES (:uid, :img, :disease, :conf, :probs, :sol, :vis_path) RETURNING id");
    $stmt->execute([
        ':uid' => $user_id,
        ':img' => 'uploads/' . $filename,
        ':disease' => $disease,
        ':conf' => $confidence,
        ':probs' => $probabilities,
        ':sol' => $solution,
        ':vis_path' => $visualization_path
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $inserted_id = $row['id'] ?? null;
} catch (PDOException $e) {
    // FALLBACK: If the 'visualization_path' column does not exist, try without it
    try {
        $stmt = $pdo->prepare("INSERT INTO predictions (user_id, image_path, disease, confidence, probabilities, solution) VALUES (:uid, :img, :disease, :conf, :probs, :sol) RETURNING id");
        $stmt->execute([
            ':uid' => $user_id,
            ':img' => 'uploads/' . $filename,
            ':disease' => $disease,
            ':conf' => $confidence,
            ':probs' => $probabilities,
            ':sol' => $solution
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $inserted_id = $row['id'] ?? null;
    } catch (PDOException $e_fallback) {
        $_SESSION['analysis_error'] = "DB insert failed: " . $e_fallback->getMessage();
        file_put_contents($logFile, "DB Error: " . $e_fallback->getMessage() . "\n", FILE_APPEND);
        header('Location: dashboard.php');
        exit;
    }
}

// Put a summary into session for dashboard display
$_SESSION['analysis'] = [
    'id' => $inserted_id,
'disease' => $disease,
'confidence' => round($confidence * 100, 2),
'solution' => $solution,
'visualization_path' => $visualization_path
];

header('Location: dashboard.php');
exit;
