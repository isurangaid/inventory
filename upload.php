<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

// Setup database connection (update if needed)
//$conn = new mysqli("localhost", "root", "", "demo");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Invalid request method.";
    exit;
}

// Check if files exist
if (empty($_FILES['photos']['tmp_name']) || !is_array($_FILES['photos']['tmp_name'])) {
    http_response_code(400);
    echo "No files received.";
    exit;
}

$anyUploaded = false;

foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
    if (!is_uploaded_file($tmpName)) continue;

    if (filesize($tmpName) > 10 * 1024 * 1024) {
        http_response_code(400);
        echo "File too large (max 10MB)";
        exit;
    }

    $originalName = $_FILES['photos']['name'][$key];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    // Validate extension
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) continue;

    $tempPath = UPLOAD_DIR . uniqid('temp_') . '.' . $ext;
    error_log("Trying to move to: " . $tempPath);
    if (!move_uploaded_file($tmpName, $tempPath)) {
        continue;
    }

    // Resize
    list($width, $height) = getimagesize($tempPath);
    $scale = MAX_WIDTH / $width;
    $newWidth = MAX_WIDTH;
    $newHeight = intval($height * $scale);

    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $srcImage = imagecreatefromjpeg($tempPath);
            break;
        case 'png':
            $srcImage = imagecreatefrompng($tempPath);
            break;
        case 'webp':
            $srcImage = imagecreatefromwebp($tempPath);
            break;
        default:
            unlink($tempPath);
            continue 2; // Skip this file
    }

    if (!$srcImage) {
        unlink($tempPath);
        continue;
    }

    $dstImage = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Add watermark if enabled
    if (ENABLE_WATERMARK) {
        $wm = imagecreatefrompng(WATERMARK_IMAGE);
        imagealphablending($dstImage, true);
        imagesavealpha($dstImage, true);

        $wmWidth = imagesx($wm);
        $wmHeight = imagesy($wm);

        switch (WATERMARK_POSITION) {
            case 'top-left':    $x = 10; $y = 10; break;
            case 'top-right':   $x = $newWidth - $wmWidth - 10; $y = 10; break;
            case 'bottom-left': $x = 10; $y = $newHeight - $wmHeight - 10; break;
            default:            $x = $newWidth - $wmWidth - 10; $y = $newHeight - $wmHeight - 10; break;
        }

        imagecopy($dstImage, $wm, $x, $y, 0, 0, $wmWidth, $wmHeight);
        imagedestroy($wm);
    }

    // Save final image
    $finalName = uniqid('img_') . '.jpg';
    $finalPath = UPLOAD_DIR . $finalName;

    if (!imagejpeg($dstImage, $finalPath, IMAGE_QUALITY)) {
        imagedestroy($srcImage);
        imagedestroy($dstImage);
        unlink($tempPath);
        continue;
    }

    // Save to DB
    //$stmt = $conn->prepare("INSERT INTO images (file_path) VALUES (?)");
    //$stmt->bind_param("s", $finalName);
    //$stmt->execute();
    $printer_id = $_POST["printer_id"];
    $last_maintenance_id_q = $conn->query("SELECT * FROM printer_maintenance WHERE printer_id = '".$printer_id."' ORDER BY id DESC LIMIT 1");
    //$last_maintenance_id_ = $conn->query($sql);
    $table_row_id = 0;
    if ($last_maintenance_id_q->num_rows === 0) {
        http_response_code(400);
        echo "Upload failed. Please check or try again.";
        exit();
    } else {
        $last_maintenance_id = $last_maintenance_id_q->fetch_assoc();
        $sql = "INSERT INTO printer_maintenance_bills (
            maintenance_id, file_name
        ) VALUES (
            '".$last_maintenance_id["id"]."', '$finalName'
        )";
        if ($conn->query($sql)) {
            $table_row_id = $conn->insert_id;
        }
    }
    

    // Clean up
    imagedestroy($srcImage);
    imagedestroy($dstImage);
    unlink($tempPath);

    $anyUploaded = true;
}

if (($anyUploaded) && ($table_row_id>0)) {
    http_response_code(200);
    echo "OK";
} else {
    http_response_code(400);
    echo "Upload failed. Please check or try again.";
}
