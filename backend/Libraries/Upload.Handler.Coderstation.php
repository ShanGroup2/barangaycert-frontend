<?php
// This class handles file uploads (saving uploaded files to the server)
class Upload_Handler
{
    // Public static method: saves an uploaded file to a target directory
    // Parameters:
    //   $file      - the file array from $_FILES['fieldname'] (contains name, tmp_name, error, etc.)
    //   $targetDir - where to save the file (default: 'uploads/' folder)
    public static function save($file, $targetDir = 'uploads/')
    {
        // Check if the target directory exists
        if (!is_dir($targetDir)) {
            // If not, create it with full permissions (0777 means read/write/execute for everyone)
            // 'true' means create nested directories if needed (e.g., 'uploads/images/')
            mkdir($targetDir, 0777, true);
        }

        // Check if the file was uploaded without errors
        // UPLOAD_ERR_OK (value 0) means no error occurred
        if ($file['error'] !== UPLOAD_ERR_OK) {
            // Return an error array (not success)
            return ['success' => false, 'error' => 'File upload error.'];
        }

        // Get the file extension (e.g., 'jpg', 'png', 'pdf')
        // pathinfo() extracts info from the original filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

        // Define which file extensions are allowed (whitelist)
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

        // Check if the extension is in the allowed list (case-insensitive)
        if (!in_array(strtolower($ext), $allowed)) {
            // Return error if file type is not allowed
            return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, PDF allowed.'];
        }

        // Generate a unique filename using uniqid() (e.g., "5f8a7b9c3d2e1.jpg")
        // This prevents filename collisions and hides original names
        $filename = uniqid() . '.' . $ext;

        // Build the full destination path (remove trailing slash from targetDir, then add filename)
        $destination = rtrim($targetDir, '/') . '/' . $filename;

        // Move the uploaded temporary file to the final destination
        // move_uploaded_file() is safer than copy() because it checks if the file was really uploaded
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Success: return true and the new filename (so the caller can store it in database)
            return ['success' => true, 'filename' => $filename];
        }
        // If move failed (e.g., permissions or disk full), return error
        return ['success' => false, 'error' => 'Failed to save file.'];
    }
}
