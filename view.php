<?php
require_once 'db_config.php';

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$uniqueId = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM images WHERE unique_id = ?");
    $stmt->execute([$uniqueId]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$image) {
        http_response_code(404);
        die("Image not found.");
    }
    
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head><meta charset="UTF-8"><title>Image Viewer</title></head>';
    echo '<body style="margin:0; background:#222; display:flex; justify-content:center; align-items:center; height:100vh;">';
    echo '<img src="' . htmlspecialchars($image['file_path']) . '" alt="' . htmlspecialchars($image['original_filename']) . '" style="max-width:95%; max-height:95%; object-fit:contain;">';
    echo '</body>';
    echo '</html>';

} catch (PDOException $e) {
    http_response_code(500);
    die("Database error: " . $e->getMessage());
}
?>
