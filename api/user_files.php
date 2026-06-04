<?php
/**
 * User Files Management API
 * Handles uploading, retrieving, and deleting user artwork files
 */

session_start();
error_reporting(0);
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Only allow logged-in users
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'];

// Create table if not exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tbl_user_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(512) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
} catch (PDOException $e) {
    // Table might already exist
}

$response = ['success' => false, 'message' => 'Invalid request method'];

try {
    if ($method === 'POST') {
        // Upload file
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
            exit;
        }

        $file = $_FILES['file'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'application/illustrator', 'application/postscript'];
        $maxSize = 500 * 1024 * 1024; // 500MB

        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP, PDF, Illustrator, Postscript']);
            exit;
        }

        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'File too large. Maximum size: 500MB']);
            exit;
        }

        // Create user directory if not exists
        $uploadDir = '../uploads/user_files/' . $user_id . '/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $filename;

        // Move file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save file']);
            exit;
        }

        // Save to database
        $stmt = $pdo->prepare("
            INSERT INTO tbl_user_files (user_id, filename, original_filename, file_path, file_size, mime_type)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $filename,
            $file['name'],
            $filePath,
            $file['size'],
            $file['type']
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'File uploaded successfully',
            'file_id' => $pdo->lastInsertId(),
            'filename' => $filename,
            'original_filename' => $file['name']
        ]);

    } elseif ($method === 'GET') {
        // Get user's files
        $stmt = $pdo->prepare("
            SELECT id, filename, original_filename, file_path, file_size, mime_type, uploaded_at
            FROM tbl_user_files
            WHERE user_id = ?
            ORDER BY uploaded_at DESC
        ");
        $stmt->execute([$user_id]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $files
        ]);

    } elseif ($method === 'DELETE') {
        // Delete file
        $fileId = $_GET['id'] ?? 0;

        // Get file info
        $stmt = $pdo->prepare("SELECT * FROM tbl_user_files WHERE id = ? AND user_id = ?");
        $stmt->execute([$fileId, $user_id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$file) {
            echo json_encode(['success' => false, 'message' => 'File not found']);
            exit;
        }

        // Delete physical file
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }

        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM tbl_user_files WHERE id = ? AND user_id = ?");
        $stmt->execute([$fileId, $user_id]);

        echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$pdo = null;
