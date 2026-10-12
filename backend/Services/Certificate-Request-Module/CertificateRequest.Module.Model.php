<?php

/**
 * CertificateRequest.Module.Model.php
 * Business logic for Certificate Request operations.
 *
 * Provides methods to submit, cancel, resubmit requests and fetch a user's requests.
 */
require_once __DIR__ . '/../../Libraries/PdoMySql.Coderstation.php';
require_once __DIR__ . '/../../Libraries/Coversion.Coderstation.php';
require_once __DIR__ . '/../../Libraries/Session.Coderstation.php';

class CertificateRequest_Module_Model
{
    /**
     * Submit a new certificate request.
     *
     * @param array $param Expected keys: 'certificate_type', 'purpose', 'quantity'
     * @return array Standard response: ['Status' => 'Success'|'Error', 'Message' => string]
     */
    public static function submit($param)
    {
        // Ensure user is logged in
        $user_id = Session::get('user_id');
        if (!$user_id) {
            return array("Status" => "Error", "Message" => "Not logged in.");
        }

        // Ensure account is verified before allowing requests
        $verification = Session::get('verification_status');
        if ($verification !== 'approved') {
            return array("Status" => "Error", "Message" => "Account not yet verified.");
        }

        // Prepare parameters for stored procedure call
        $params = array($user_id, $param['certificate_type'], $param['purpose'], $param['quantity']);
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Certificate_Submit(?,?,?,?)",
            $params
        );

        // Normalize DB response and return a friendly message
        $Response = Data_Coversion::toArray($Response);
        $Message = $Response[0]["Record_Status"] ?? "Request submitted.";

        // Fetch the submitted request to return the data
        $RequestId = $Response[0]["id"] ?? $Response[0]["request_id"] ?? null;
        if ($RequestId) {
            $FetchedRequest = PdoMySql::ExecuteDML_Query(
                Application::$DBase,
                "CALL Certificate_MyRequests(?,?)",
                array($user_id, null)
            );
            $FetchedRequest = Data_Coversion::toArray($FetchedRequest);
            return array("Status" => "Success", "Message" => $Message, "Data" => $FetchedRequest);
        }

        return array("Status" => "Success", "Message" => $Message, "Data" => $Response);
    }

    /**
     * Cancel an existing certificate request.
     *
     * @param array $param Expected to contain the request id or cancellation payload
     * @return array Standard response with status and message
     */
    public static function cancel($param)
    {
        // Call stored procedure to cancel the request
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Certificate_Cancel(?)",
            Data_Coversion::toArray($param)
        );

        // Normalize DB response and return a friendly message
        $Response = Data_Coversion::toArray($Response);
        $Message = $Response[0]["Record_Status"] ?? "Request cancelled.";

        // Fetch user's requests after cancellation
        $user_id = Session::get('user_id');
        $FetchedRequests = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Certificate_MyRequests(?,?)",
            array($user_id, null)
        );
        $FetchedRequests = Data_Coversion::toArray($FetchedRequests);

        return array("Status" => "Success", "Message" => $Message, "Data" => $FetchedRequests);
    }

    /**
     * Resubmit a previously cancelled/adjusted request (e.g., update quantity).
     *
     * @param array $param Expected keys depend on stored procedure (id, quantity)
     * @return array Standard response with status and message
     */
    public static function resubmit($param)
    {
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Certificate_Resubmit(?,?)",
            Data_Coversion::toArray($param)
        );
        $Response = Data_Coversion::toArray($Response);
        $Message = $Response[0]["Record_Status"] ?? "Request resubmitted.";

        // Fetch user's requests after resubmission
        $user_id = Session::get('user_id');
        $FetchedRequests = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Certificate_MyRequests(?,?)",
            array($user_id, null)
        );
        $FetchedRequests = Data_Coversion::toArray($FetchedRequests);

        return array("Status" => "Success", "Message" => $Message, "Data" => $FetchedRequests);
    }

    /**
     * Retrieve requests created by the current user, optionally filtered by status.
     *
     * @param array $param Optional key: 'status' to filter results
     * @return array ['Status' => 'Success'|'Error', 'Data' => array]
     */
    public static function myRequests($param)
    {
        // Ensure user is logged in
        $user_id = Session::get('user_id');
        if (!$user_id) {
            return array("Status" => "Error", "Message" => "Not logged in.");
        }

        $status = isset($param['status']) ? $param['status'] : null;
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Certificate_MyRequests(?,?)",
            array($user_id, $status)
        );
        $Response = Data_Coversion::toArray($Response);
        return array("Status" => "Success", "Data" => $Response);
    }
}
