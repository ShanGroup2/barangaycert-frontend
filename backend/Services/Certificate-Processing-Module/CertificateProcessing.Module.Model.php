<?php
// Include the database helper library (PdoMySql) from the Coderstation folder
require_once __DIR__ . '/../../Libraries/PdoMySql.Coderstation.php';
// Include the data conversion helper library
require_once __DIR__ . '/../../Libraries/Coversion.Coderstation.php';
// Include the session management library
require_once __DIR__ . '/../../Libraries/Session.Coderstation.php';

// This class handles everything related to certificate processing (approve, reject, print, etc.)
class CertificateProcessing_Module_Model
{
    // Private helper method: checks if the current logged-in user is admin or BHW
    private static function isAdminOrBhw()
    {
        // Get the 'role' value stored in the session (like a temporary ID card for the user)
        $role = Session::get('role');
        // Return true if role is 'admin' or 'bhw', otherwise false
        return in_array($role, ['admin', 'bhw'], true);
    }

    // Private helper method: stops the function if user is NOT admin/BHW
    private static function requireAdminOrBhw()
    {
        // Call the method above to check access
        if (!self::isAdminOrBhw()) {
            // If not authorized, return an error array with message
            return array("Status" => "Error", "Message" => "Unauthorized: Only admin or BHW can access this function.");
        }
        // If authorized, return null (meaning "all good, continue")
        return null;
    }

    // Public method: approve a certificate request
    public static function approve($param)
    {
        // First, check if the current user is allowed (admin or BHW)
        $authError = self::requireAdminOrBhw();
        // If $authError is not null, it means there was an error → return that error immediately
        if ($authError !== null) return $authError;

        // Call a stored procedure in the database named 'Certificate_Approve'
        // Pass the request parameters (like request ID) to that procedure
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,                 // database connection object
            "CALL Certificate_Approve(?,?)",     // SQL command to run
            Data_Coversion::toArray($param)      // convert parameters into a safe array
        );
        // Convert the database result into a PHP array
        $Response = Data_Coversion::toArray($Response);
        // Get the success message from the result, or use a default message
        $Message = $Response[0]["Record_Status"] ?? "Request approved.";

        // Fetch the updated request data
        $RequestId = $param['request_id'] ?? $param['id'] ?? null;
        if ($RequestId) {
            $UpdatedRequest = PdoMySql::ExecuteDML_Query(
                Application::$DBase,
                "SELECT * FROM certificate_requests WHERE id = ?",
                array($RequestId)
            );
            $UpdatedRequest = Data_Coversion::toArray($UpdatedRequest);
            return array("Status" => "Success", "Message" => $Message, "Data" => $UpdatedRequest);
        }
        // Return a success response with the message
        return array("Status" => "Success", "Message" => $Message);
    }

    // Public method: reject a certificate request
    public static function reject($param)
    {
        // Same authorization check as in approve()
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;

        // Call the stored procedure 'Certificate_Reject'
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Certificate_Reject(?,?)",
            Data_Coversion::toArray($param)
        );
        $Response = Data_Coversion::toArray($Response);
        $Message = $Response[0]["Record_Status"] ?? "Request rejected.";

        // Fetch the updated request data
        $RequestId = $param['request_id'] ?? $param['id'] ?? null;
        if ($RequestId) {
            $UpdatedRequest = PdoMySql::ExecuteDML_Query(
                Application::$DBase,
                "SELECT * FROM certificate_requests WHERE id = ?",
                array($RequestId)
            );
            $UpdatedRequest = Data_Coversion::toArray($UpdatedRequest);
            return array("Status" => "Success", "Message" => $Message, "Data" => $UpdatedRequest);
        }
        return array("Status" => "Success", "Message" => $Message);
    }

    // Public method: fetch certificate data needed for printing
    public static function printCertificate($param)
    {
        // Try to get the request_id from the input parameter array
        $requestId = $param['request_id'] ?? null;
        // If no request ID given, return nothing (null)
        if (!$requestId) {
            return null;
        }

        // Run a SELECT query to get certificate details + user email + resident info
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "SELECT cr.*, u.email, r.full_name, r.address, r.birthdate, r.gender
             FROM certificate_requests cr
             JOIN users u ON cr.user_id = u.id
             JOIN residents r ON u.resident_id = r.id
             WHERE cr.id = ?",
            array($requestId)
        );
        // Convert result to array and return the first row (the certificate data)
        $Response = Data_Coversion::toArray($Response);
        return $Response[0] ?? null;
    }

    // Public method: mark a certificate request as "completed"
    public static function complete($param)
    {
        // Authorize the user (admin/BHW only)
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;

        // Call stored procedure 'Certificate_Complete' with one parameter (e.g., request ID)
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Certificate_Complete(?)",
            Data_Coversion::toArray($param)
        );
        $Response = Data_Coversion::toArray($Response);
        $Message = $Response[0]["Record_Status"] ?? "Completed.";

        // Fetch the updated request data
        $RequestId = $param['request_id'] ?? $param['id'] ?? null;
        if ($RequestId) {
            $UpdatedRequest = PdoMySql::ExecuteDML_Query(
                Application::$DBase,
                "SELECT * FROM certificate_requests WHERE id = ?",
                array($RequestId)
            );
            $UpdatedRequest = Data_Coversion::toArray($UpdatedRequest);
            return array("Status" => "Success", "Message" => $Message, "Data" => $UpdatedRequest);
        }
        return array("Status" => "Success", "Message" => $Message);
    }

    // Public method: generate a yearly report of completed certificates
    public static function yearlyReport($param)
    {
        // Authorize the user
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;

        // Get the year from parameters, default to current year if not provided
        $year = $param['year'] ?? date('Y');
        // Call stored procedure 'Report_Completed_By_Year' with the year
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Report_Completed_By_Year(?)",
            array($year)
        );
        $Response = Data_Coversion::toArray($Response);
        // Return the report data (a list of results)
        return array("Status" => "Success", "Data" => $Response);
    }

    // Public method: get counts for dashboard (pending requests + pending verifications)
    public static function dashboardCounts()
    {
        // Authorize the user
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;

        // Count how many certificate requests have status = 'pending'
        $pendingRequests = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "SELECT COUNT(*) as count FROM certificate_requests WHERE status = 'pending'"
        );
        // Count how many users have verification_status = 'pending'
        $pendingVerifications = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "SELECT COUNT(*) as count FROM users WHERE verification_status = 'pending'"
        );
        // Convert both results to arrays
        $pendingRequests = Data_Coversion::toArray($pendingRequests);
        $pendingVerifications = Data_Coversion::toArray($pendingVerifications);
        // Return a structured array with the two counts
        return array(
            "Status" => "Success",
            "Data" => array(
                "pending_requests" => $pendingRequests[0]['count'] ?? 0,
                "pending_verifications" => $pendingVerifications[0]['count'] ?? 0
            )
        );
    }

    // Public method: get list of all pending certificate requests (with resident names)
    public static function pendingList()
    {
        // Authorize the user
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;

        // Select all pending requests, join with users and residents to get the full name
        // Order by requested_at date from oldest to newest (ASC)
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "SELECT cr.*, r.full_name
             FROM certificate_requests cr
             JOIN users u ON cr.user_id = u.id
             JOIN residents r ON u.resident_id = r.id
             WHERE cr.status = 'pending' ORDER BY cr.requested_at ASC"
        );
        $Response = Data_Coversion::toArray($Response);
        // Return the list of pending requests
        return array("Status" => "Success", "Data" => $Response);
    }

    public static function staffRequests($param)
    {
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;

        $status = $param['status'] ?? '';
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "SELECT cr.*, r.full_name
             FROM certificate_requests cr
             JOIN users u ON cr.user_id = u.id
             JOIN residents r ON u.resident_id = r.id
             WHERE (? = '' OR cr.status = ?)
             ORDER BY cr.requested_at DESC",
            array($status, $status)
        );
        $Response = Data_Coversion::toArray($Response);
        return array("Status" => "Success", "Data" => $Response);
    }
}
