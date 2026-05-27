<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class AttachmentController
{
    private \PDO $db;
    private array $config;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    public function upload(Request $request, array $params): void
    {
        $taskId = (int) $params['id'];

        // Verify task exists
        $stmt = $this->db->prepare('SELECT id FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $taskId]);
        if (!$stmt->fetch()) {
            Response::error('Task not found', 404);
        }

        $file = $request->file('attachment');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Response::error('No file uploaded or upload error', 400);
        }

        $errors = $this->validateFile($file);
        if (!empty($errors)) {
            Response::error('File validation failed', 422, $errors);
        }

        $uploadDir = $this->config['upload_dir'] . '/tasks/' . $taskId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . '/' . $fileName;
        
        $dbPath = '/uploads/tasks/' . $taskId . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            Response::error('Failed to save file', 500);
        }

        // Generate thumbnail for images
        if (strpos($file['type'], 'image/') === 0) {
            $this->generateThumbnail($filePath, $file['type']);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO task_attachments (task_id, file_name, file_path, file_size, mime_type)
             VALUES (:task_id, :file_name, :file_path, :file_size, :mime_type)'
        );

        $stmt->execute([
            'task_id' => $taskId,
            'file_name' => $file['name'],
            'file_path' => $dbPath,
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
        ]);

        $attachmentId = $this->db->lastInsertId();
        
        $stmt = $this->db->prepare('SELECT * FROM task_attachments WHERE id = :id');
        $stmt->execute(['id' => $attachmentId]);
        $attachment = $stmt->fetch();
        
        $attachment['id'] = (int) $attachment['id'];
        $attachment['task_id'] = (int) $attachment['task_id'];
        $attachment['file_size'] = (int) $attachment['file_size'];

        Response::success($attachment, 'File uploaded successfully', 201);
    }

    public function download(Request $request, array $params): void
    {
        $attachmentId = (int) $params['id'];

        $stmt = $this->db->prepare('SELECT * FROM task_attachments WHERE id = :id');
        $stmt->execute(['id' => $attachmentId]);
        $attachment = $stmt->fetch();

        if (!$attachment) {
            Response::error('Attachment not found', 404);
        }

        $filePath = __DIR__ . '/../../storage' . $attachment['file_path'];

        if (!file_exists($filePath)) {
            Response::error('File not found on server', 404);
        }

        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . $attachment['file_name'] . '"');
        header('Content-Length: ' . filesize($filePath));
        
        readfile($filePath);
        exit;
    }

    public function destroy(Request $request, array $params): void
    {
        $attachmentId = (int) $params['id'];

        $stmt = $this->db->prepare('SELECT * FROM task_attachments WHERE id = :id');
        $stmt->execute(['id' => $attachmentId]);
        $attachment = $stmt->fetch();

        if (!$attachment) {
            Response::error('Attachment not found', 404);
        }

        $filePath = __DIR__ . '/../../storage' . $attachment['file_path'];
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Also delete thumbnail if exists
        $thumbPath = dirname($filePath) . '/thumb_' . basename($filePath);
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }

        $stmt = $this->db->prepare('DELETE FROM task_attachments WHERE id = :id');
        $stmt->execute(['id' => $attachmentId]);

        Response::success(null, 'Attachment deleted');
    }

    private function validateFile(array $file): array
    {
        $errors = [];

        if ($file['size'] > $this->config['max_file_size']) {
            $errors['file'] = 'File size exceeds maximum limit of ' . ($this->config['max_file_size'] / 1024 / 1024) . 'MB';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->config['allowed_mime_types'])) {
            $errors['file'] = 'File type not allowed: ' . $mimeType;
        }

        return $errors;
    }
    
    private function generateThumbnail(string $filePath, string $mimeType): void
    {
        $maxWidth = 150;
        $maxHeight = 150;
        
        list($width, $height) = getimagesize($filePath);
        
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int) ($width * $ratio);
        $newHeight = (int) ($height * $ratio);
        
        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        
        // Handle transparency
        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
            imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        $source = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($filePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $source = imagecreatefromwebp($filePath);
                }
                break;
        }
        
        if ($source) {
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            $thumbPath = dirname($filePath) . '/thumb_' . basename($filePath);
            
            switch ($mimeType) {
                case 'image/jpeg':
                    imagejpeg($thumb, $thumbPath, 85);
                    break;
                case 'image/png':
                    imagepng($thumb, $thumbPath, 8);
                    break;
                case 'image/gif':
                    imagegif($thumb, $thumbPath);
                    break;
                case 'image/webp':
                    if (function_exists('imagewebp')) {
                        imagewebp($thumb, $thumbPath, 85);
                    }
                    break;
            }
            
            imagedestroy($source);
        }
        
        imagedestroy($thumb);
    }
}
