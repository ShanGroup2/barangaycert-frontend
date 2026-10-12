<?php
// Include the Model class (handles database logic for authentication)
require_once __DIR__ . "/Authentication.Module.Model.php";
// Include the View class (handles formatting the response, e.g., to JSON)
require_once __DIR__ . "/Authentication.Module.View.php";

// This class is the Controller: it listens for HTTP requests and calls the right Model method
class Authentication_Module_Controller
{
    // The dispatch method is the entry point – it sets up all the API routes
    public static function dispatch($request)
    {
        // T01: Resident Sign-up
        // Define a POST route at 'api/auth/signup'
        // When a POST request comes to that URL, the following function runs
        Route::post('api/auth/signup', function ($request) {
            // Call the signup method from the Model, passing the request body (e.g., email, password)
            $data = Authentication_Module_Model::signup($request['body']);
            // Format the response (e.g., turn array into JSON) using the View and return it
            return Authentication_Module_View::formatResponse($data);
        });

        // T02: Resident Login
        // POST endpoint for logging in – calls Model::login with request body
        Route::post('api/auth/login', function ($request) {
            $data = Authentication_Module_Model::login($request['body']);
            return Authentication_Module_View::formatResponse($data);
        });

        // T03: Upload Verification Document (multipart/form-data)
        // This endpoint handles file uploads, so it uses $_POST and $_FILES directly
        Route::post('api/auth/upload-id', function ($request) {
            // Pass the POST fields and uploaded files to the Model's uploadID method
            $data = Authentication_Module_Model::uploadID($_POST, $_FILES);
            return Authentication_Module_View::formatResponse($data);
        });

        // T04: Admin Approves Account Verification
        // Admin-only endpoint – calls Model::approveVerification with request body (e.g., user_id)
        Route::post('api/auth/verify/approve', function ($request) {
            $data = Authentication_Module_Model::approveVerification($request['body']);
            return Authentication_Module_View::formatResponse($data);
        });

        // T04b: Admin Rejects Account Verification
        // Admin-only endpoint – calls Model::rejectVerification (may include rejection reason)
        Route::post('api/auth/verify/reject', function ($request) {
            $data = Authentication_Module_Model::rejectVerification($request['body']);
            return Authentication_Module_View::formatResponse($data);
        });

        Route::get('api/auth/verify/pending', function ($request) {
            $data = Authentication_Module_Model::pendingVerifications();
            return Authentication_Module_View::formatResponse($data);
        });

        Route::get('api/auth/verify/document', function ($request) {
            $document = Authentication_Module_Model::verificationDocument($request['params'] ?? array());
            if (($document['Status'] ?? '') !== 'Success') {
                return Authentication_Module_View::formatResponse($document);
            }

            header('Content-Type: ' . $document['ContentType']);
            header('Content-Disposition: inline; filename="' . $document['DownloadName'] . '"');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, no-store');
            readfile($document['Path']);
            exit;
        });

        Route::get('api/auth/accounts', function ($request) {
            $data = Authentication_Module_Model::accounts();
            return Authentication_Module_View::formatResponse($data);
        });

        Route::post('api/auth/accounts/role', function ($request) {
            $data = Authentication_Module_Model::updateAccountRole($request['body']);
            return Authentication_Module_View::formatResponse($data);
        });

        // Get current user profile (requires user to be logged in)
        // GET endpoint – no body needed; Model::getProfile reads session to identify user
        Route::get('api/auth/profile', function ($request) {
            $data = Authentication_Module_Model::getProfile();
            return Authentication_Module_View::formatResponse($data);
        });

        // Change password (logged-in user only)
        // POST endpoint – expects old_password and new_password in request body
        Route::post('api/auth/change-password', function ($request) {
            $data = Authentication_Module_Model::changePassword($request['body']);
            return Authentication_Module_View::formatResponse($data);
        });

        // Logout (destroys session)
        // POST endpoint – no parameters needed; Model::logout clears session data
        Route::post('api/auth/logout', function ($request) {
            $data = Authentication_Module_Model::logout();
            return Authentication_Module_View::formatResponse($data);
        });
    }
}
