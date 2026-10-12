<?php
// Include database helper to run SQL queries safely
require_once __DIR__ . '/../../Libraries/PdoMySql.Coderstation.php';
// Include data conversion helper (probably converts objects/results to arrays)
require_once __DIR__ . '/../../Libraries/Coversion.Coderstation.php';
// Include session management helper (store/retrieve user data across pages)
require_once __DIR__ . '/../../Libraries/Session.Coderstation.php';
// Include file upload helper (handles saving uploaded files to server)
require_once __DIR__ . '/../../Libraries/Upload.Handler.Coderstation.php';

// This class manages user authentication: signup, login, profile, password, etc.
class Authentication_Module_Model
{
    // Public method: create a new user account
    public static function signup($param)
    {
        // Call stored procedure 'User_Signup' with parameters from $param
        // The three '?' placeholders will be filled with values from $param (converted to array)
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,                 // database connection
            "CALL User_Signup(?,?,?)",           // SQL command (e.g., email, password, name)
            Data_Coversion::toArray($param)      // convert input to array for safety
        );
        // Convert database response (maybe a result set) into a PHP array
        $Response = Data_Coversion::toArray($Response);
        // Get the status message from the first row, or use default message
        $Message = $Response[0]["Record_Status"] ?? "Account created.";
        // Remove the "Record_Status" from the data array so it's not returned twice
        unset($Response[0]["Record_Status"]);
        // Return success response with message and any extra data (e.g., user ID)
        return array("Status" => "Success", "Message" => $Message, "Data" => $Response);
    }

    // Public method: log an existing user in
    public static function login($param)
    {
        // Call stored procedure 'User_Login' (expects e.g., email/username and password)
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL User_Login(?,?)",
            Data_Coversion::toArray($param)
        );
        $Response = Data_Coversion::toArray($Response);
        // Check if the login succeeded (the result contains a 'user_id' field)
        if (isset($Response[0]['user_id'])) {
            // Store user information in the session (like a temporary ID card for the user)
            Session::set('user_id', $Response[0]['user_id']);
            Session::set('role', $Response[0]['role']);                    // 'admin', 'bhw', or 'resident'
            Session::set('verification_status', $Response[0]['verification_status']);
            // Return success with user data
            return array("Status" => "Success", "Message" => "Login successful", "Data" => $Response[0]);
        } else {
            // Login failed: return error message from database or generic message
            return array("Status" => "Error", "Message" => $Response[0]['error'] ?? "Invalid credentials");
        }
    }

    // Public method: upload an identification document (ID) for verification
    public static function uploadID($post, $files)
    {
        // Get logged-in user's ID from session (set during login)
        $user_id = Session::get('user_id');
        // If no user is logged in, return error
        if (!$user_id) {
            return array("Status" => "Error", "Message" => "Not logged in.");
        }
        // Check if a file was uploaded under the field name 'verification_doc'
        if (!isset($files['verification_doc'])) {
            return array("Status" => "Error", "Message" => "No file uploaded.");
        }
        // Use Upload_Handler to save the file to the 'uploads/' folder on the server
        $uploadResult = Upload_Handler::save($files['verification_doc'], __DIR__ . '/../../uploads/');
        // If upload failed (e.g., file too large, wrong type), return the error
        if (!$uploadResult['success']) {
            return array("Status" => "Error", "Message" => $uploadResult['error']);
        }
        // Call stored procedure 'User_UploadID' to store the filename in the database
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL User_UploadID(?,?)",
            array($user_id, $uploadResult['filename'])   // user ID and saved filename
        );
        $Response = Data_Coversion::toArray($Response);
        $Message = $Response[0]["Record_Status"] ?? "ID uploaded.";
        // Return success response
        return array("Status" => "Success", "Message" => $Message);
    }

    // Private helper: checks if current user is admin or BHW (Barangay Health Worker)
    private static function isAdminOrBhw()
    {
        $role = Session::get('role');   // Get role from session
        // Return true if role is 'admin' or 'bhw', otherwise false
        return in_array($role, ['admin', 'bhw'], true);
    }

    private static function requireAdmin()
    {
        if (Session::get('role') !== 'admin') {
            return array("Status" => "Error", "Message" => "Unauthorized: Only admin can manage staff access.");
        }
        return null;
    }

    // Private helper: stops execution if user is not admin/BHW
    private static function requireAdminOrBhw()
    {
        if (!self::isAdminOrBhw()) {
            // Return an error array (the caller will return this as the response)
            return array("Status" => "Error", "Message" => "Unauthorized: Only admin or BHW can access this function.");
        }
        // If authorized, return null (meaning "all good")
        return null;
    }

    // Public method: admin/BHW approves a user's ID verification
    public static function approveVerification($param)
    {
        // Check authorization (only admin/BHW can approve)
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;   // If not authorized, return the error

        // Call stored procedure 'User_Verify_Approve' with one parameter (e.g., user_id or request_id)
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL User_Verify_Approve(?)",
            Data_Coversion::toArray($param)
        );
        $Response = Data_Coversion::toArray($Response);
        // Return success with message from database or default
        return array("Status" => "Success", "Message" => $Response[0]["Record_Status"] ?? "Approved");
    }

    // Public method: admin/BHW rejects a user's ID verification (optionally with a reason)
    public static function rejectVerification($param)
    {
        // Authorization check again
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;

        // Call stored procedure 'User_Verify_Reject' (expects user_id and maybe reason)
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL User_Verify_Reject(?,?)",
            Data_Coversion::toArray($param)
        );
        $Response = Data_Coversion::toArray($Response);
        return array("Status" => "Success", "Message" => $Response[0]["Record_Status"] ?? "Rejected");
    }

    public static function pendingVerifications()
    {
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;

        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "SELECT u.id, u.email, u.verification_status, u.verification_doc, u.created_at,
                    r.full_name, r.address, r.contact_number
             FROM users u
             JOIN residents r ON u.resident_id = r.id
             WHERE u.verification_status = 'pending'
             ORDER BY u.created_at ASC"
        );
        $Response = Data_Coversion::toArray($Response);
        return array("Status" => "Success", "Data" => $Response);
    }

    public static function verificationDocument($param)
    {
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;

        $userId = $param['user_id'] ?? null;
        if (!$userId) {
            return array("Status" => "Error", "Message" => "A user ID is required.");
        }

        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "SELECT verification_doc FROM users WHERE id = ?",
            array($userId)
        );
        $Response = Data_Coversion::toArray($Response);
        if (isset($Response[0]['Status']) && strpos($Response[0]['Status'], 'Error:') === 0) {
            return array("Status" => "Error", "Message" => $Response[0]['Status']);
        }

        $filename = $Response[0]['verification_doc'] ?? '';
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $contentTypes = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf'
        );
        if ($filename === '' || basename($filename) !== $filename || !isset($contentTypes[$extension])) {
            return array("Status" => "Error", "Message" => "Verification document not found.");
        }

        $uploadDirectory = realpath(__DIR__ . '/../../uploads');
        $path = realpath(__DIR__ . '/../../uploads/' . $filename);
        if ($uploadDirectory === false || $path === false || !is_file($path) ||
            strpos($path, $uploadDirectory . DIRECTORY_SEPARATOR) !== 0) {
            return array("Status" => "Error", "Message" => "Verification document not found.");
        }

        return array(
            "Status" => "Success",
            "Path" => $path,
            "ContentType" => $contentTypes[$extension],
            "DownloadName" => 'verification-document.' . $extension
        );
    }

    public static function accounts()
    {
        $authError = self::requireAdmin();
        if ($authError !== null) return $authError;

        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "SELECT u.id, u.email, u.role, u.verification_status, r.full_name
             FROM users u
             JOIN residents r ON u.resident_id = r.id
             ORDER BY r.full_name ASC"
        );
        $Response = Data_Coversion::toArray($Response);
        return array("Status" => "Success", "Data" => $Response);
    }

    public static function updateAccountRole($param)
    {
        $authError = self::requireAdmin();
        if ($authError !== null) return $authError;

        $userId = $param['user_id'] ?? null;
        $role = $param['role'] ?? '';
        if (!$userId || !in_array($role, ['resident', 'bhw'], true)) {
            return array("Status" => "Error", "Message" => "A valid user ID and resident or BHW role are required.");
        }

        $updated = PdoMySql::ExecuteDML_NonQuery(
            Application::$DBase,
            "UPDATE users SET role = ?, updated_at = NOW() WHERE id = ? AND role IN ('resident', 'bhw')",
            array($role, $userId)
        );
        if (!is_int($updated)) {
            $error = json_decode((string)$updated, true);
            return array("Status" => "Error", "Message" => $error['Status'] ?? "Unable to update account role.");
        }
        if ($updated === 0) {
            $existing = PdoMySql::ExecuteDML_Query(
                Application::$DBase,
                "SELECT id FROM users WHERE id = ? AND role = ? AND role IN ('resident', 'bhw')",
                array($userId, $role)
            );
            $existingRows = Data_Coversion::toArray($existing);
            if (isset($existingRows[0]['Status']) && strpos($existingRows[0]['Status'], 'Error:') === 0) {
                return array("Status" => "Error", "Message" => $existingRows[0]['Status']);
            }
            if (!empty($existingRows)) {
                return array("Status" => "Success", "Message" => "Account already has this access role.");
            }
            return array("Status" => "Error", "Message" => "Account not found or role was not changed.");
        }
        return array("Status" => "Success", "Message" => $role === 'bhw' ? "BHW access assigned." : "Resident access restored.");
    }

    // Public method: get the logged-in user's profile (including resident details)
    public static function getProfile()
    {
        // Get user ID from session
        $user_id = Session::get('user_id');
        if (!$user_id) {
            return array("Status" => "Error", "Message" => "Not logged in.");
        }
        // Select user data joined with resident table (full_name, address, etc.)
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "SELECT u.id, u.email, u.role, u.verification_status, u.created_at,
                    r.full_name, r.address, r.contact_number, r.birthdate, r.gender
             FROM users u JOIN residents r ON u.resident_id = r.id WHERE u.id = ?",
            array($user_id)
        );
        $Response = Data_Coversion::toArray($Response);
        // Return the profile data
        return array("Status" => "Success", "Data" => $Response);
    }

    // Public method: change the logged-in user's password
    public static function changePassword($param)
    {
        $user_id = Session::get('user_id');
        // First, fetch the current hashed password from database (⚠️ note: plain text comparison is unsafe)
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "SELECT password FROM users WHERE id = ?",
            array($user_id)
        );
        $user = Data_Coversion::toArray($Response);

        // Compare the provided old password with the one in database (should use password_verify() in real apps)
        if (empty($user) || $user[0]['password'] !== $param['old_password']) {
            return array("Status" => "Error", "Message" => "Old password is incorrect.");
        }

        // Update the password in the database (⚠️ storing plain text, should hash with password_hash())
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "UPDATE users SET password = ? WHERE id = ?",
            array($param['new_password'], $user_id)
        );
        return array("Status" => "Success", "Message" => "Password changed.");
    }

    // Public method: log the user out
    public static function logout()
    {
        // Destroy all session data (clear the "ID card")
        Session::destroy();
        return array("Status" => "Success", "Message" => "Logged out.");
    }
}
