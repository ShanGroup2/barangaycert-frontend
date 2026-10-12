<?php

/**
 * CertificateRequest.Module.Controller.php
 * Controller for Certificate Request module routes.
 *
 * Registers HTTP endpoints and delegates work to the Model and View layers.
 */
require_once __DIR__ . '/CertificateRequest.Module.Model.php';
require_once __DIR__ . '/CertificateRequest.Module.View.php';

/**
 * CertificateRequest_Module_Controller
 *
 * Static controller responsible for routing certificate-request related
 * API calls to the model and returning formatted responses via the view.
 */
class CertificateRequest_Module_Controller
{
    /**
     * Register routes for the certificate request module.
     *
     * @param array $request Incoming request context (provided by the router)
     */
    public static function dispatch($request)
    {
        // T09: Submit Certificate Request
        // Expects: $request['body'] => array with certificate_type, purpose, quantity
        Route::post('api/certificate-request/submit', function ($request) {
            $data = CertificateRequest_Module_Model::submit($request['body']);
            return CertificateRequest_Module_View::formatResponse($data);
        });

        // T10: Cancel Pending Request
        // Expects: $request['body'] => array with request identifier (id)
        Route::post('api/certificate-request/cancel', function ($request) {
            $data = CertificateRequest_Module_Model::cancel($request['body']);
            return CertificateRequest_Module_View::formatResponse($data);
        });

        // T11: Resubmit Request (update quantity)
        // Expects: $request['body'] => array with request id and new quantity
        Route::post('api/certificate-request/resubmit', function ($request) {
            $data = CertificateRequest_Module_Model::resubmit($request['body']);
            return CertificateRequest_Module_View::formatResponse($data);
        });

        // Get my requests (filter by status)
        // Query params: optional 'status' to filter (e.g., pending, completed)
        Route::get('api/certificate-request/my-requests', function ($request) {
            $queryParams = $request['query'] ?? $_GET ?? array();
            $data = CertificateRequest_Module_Model::myRequests($queryParams);
            return CertificateRequest_Module_View::formatResponse($data);
        });
    }
}
