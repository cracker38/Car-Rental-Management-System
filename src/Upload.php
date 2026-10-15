<?php
declare(strict_types=1);

namespace Crms;

final class Upload
{
    public static function image(array $file, string $subdir): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('File upload failed.');
        }
        $cfg = require dirname(__DIR__) . '/config/app.php';
        if (($file['size'] ?? 0) > $cfg['upload_max_bytes']) {
            throw new \RuntimeException('File is too large (max 2 MB).');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $map = $cfg['allowed_image_mimes'];
        if (!isset($map[$mime])) {
            throw new \RuntimeException('Only JPEG, PNG or WebP images are allowed.');
        }
        $dir = dirname(__DIR__) . '/uploads/' . $subdir;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create upload directory.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $map[$mime];
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Could not store uploaded file.');
        }
        return 'uploads/' . $subdir . '/' . $name;
    }
}
