<?php

namespace App\Jobs;

use App\Core\Database;
use App\Core\Queue;

class ProcessFileJob implements JobInterface
{
    public function handle(array $data): void
    {
        $attachmentId = $data['attachment_id'];
        $filePath = $data['file_path'];
        $mimeType = $data['mime_type'];
        $baseDir = __DIR__ . '/../../storage';
        $fullPath = $baseDir . $filePath;

        sleep(2);
        
        if (rand(1, 100) <= 5) {
            $this->markAsInfected($attachmentId, $fullPath);
            throw new \Exception("Virus detected in file: {$filePath}");
        }

        if (strpos($mimeType, 'image/') === 0) {
            $this->generateThumbnail($fullPath, $mimeType);
        }
    }

    private function markAsInfected(int $attachmentId, string $fullPath): void
    {
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare('UPDATE task_attachments SET file_name = CONCAT("[INFECTED] ", file_name) WHERE id = :id');
        $stmt->execute(['id' => $attachmentId]);
    }

    private function generateThumbnail(string $filePath, string $mimeType): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $maxWidth = 150;
        $maxHeight = 150;
        
        list($width, $height) = @getimagesize($filePath);
        if (!$width || !$height) return;
        
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int) ($width * $ratio);
        $newHeight = (int) ($height * $ratio);
        
        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        
        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
            imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        $source = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $source = @imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $source = @imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $source = @imagecreatefromgif($filePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $source = @imagecreatefromwebp($filePath);
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
