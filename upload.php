<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db_config.php';

$uploadDir = 'uploads/';
$maxFileSize = 5 * 1024 * 1024; // 5 MB
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) {

    $file = $_FILES["image"];

    if ($file["error"] !== UPLOAD_ERR_OK) {
        die("An error occurred during file upload. Error code: " . $file["error"]);
    }

    if ($file["size"] > $maxFileSize) {
        die("Error: File size is too large. Maximum allowed is 5MB.");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file["tmp_name"]);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimeTypes)) {
        die("Error: Only JPG, PNG, and GIF formats are allowed.");
    }

    $tempFilePath = $file["tmp_name"];
    $command = "clamscan --stdout --no-summary " . escapeshellarg($tempFilePath);
    
    exec($command, $output, $return_var);

    if ($return_var == 1) {
        unlink($tempFilePath);
        die("SECURITY ALERT: Malware detected in the uploaded file. The file has been deleted.");
    } elseif ($return_var != 0) {
        die("A system error occurred during the scan. Please try again later.");
    }
    
    $imageResource = null;
    if ($mimeType == 'image/jpeg') {
        $imageResource = imagecreatefromjpeg($tempFilePath);
    } elseif ($mimeType == 'image/png') {
        $imageResource = imagecreatefrompng($tempFilePath);
    } elseif ($mimeType == 'image/gif') {
        $imageResource = imagecreatefromgif($tempFilePath);
    }

    if ($imageResource === false) {
        die("Error: The image file is corrupt or cannot be processed.");
    }

    $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
    $uniqueId = bin2hex(random_bytes(6));
    $newFilename = $uniqueId . '.' . strtolower($extension);
    $destinationPath = $uploadDir . $newFilename;

    $saveSuccess = false;
    if ($mimeType == 'image/jpeg') {
        $saveSuccess = imagejpeg($imageResource, $destinationPath, 90);
    } elseif ($mimeType == 'image/png') {
        $saveSuccess = imagepng($imageResource, $destinationPath, 6);
    } elseif ($mimeType == 'image/gif') {
        $saveSuccess = imagegif($imageResource, $destinationPath);
    }
    
    imagedestroy($imageResource);

    if (!$saveSuccess) {
        die("Error: The file could not be saved to the server.");
    }
    
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO images (unique_id, original_filename, new_filename, file_path, file_size, mime_type, upload_ip, scan_status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 'clean')"
        );
        $stmt->execute([
            $uniqueId,
            htmlspecialchars($file["name"]),
            $newFilename,
            $destinationPath,
            $file["size"],
            $mimeType,
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (PDOException $e) {
        unlink($destinationPath);
        die("Database error: " . $e->getMessage());
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $imageUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/view.php?id=" . $uniqueId;
    
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>Upload Success</title><style>body{font-family:sans-serif; text-align:center; padding-top: 50px;}</style></head><body>";
    echo "<h1>Upload Successful!</h1>";
    echo "<p>Your image has been successfully uploaded and scanned.</p>";
    echo '<p>Your link: <input type="text" value="' . $imageUrl . '" size="50" readonly onclick="this.select();"><br><br><a href="' . $imageUrl . '">View Image</a></p>';
    echo "</body></html>";

} else {
    header("Location: index.html");
    exit();
}
?>
