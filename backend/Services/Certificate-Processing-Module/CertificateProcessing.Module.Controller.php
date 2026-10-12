<?php
require_once __DIR__ . "/CertificateProcessing.Module.Model.php";
require_once __DIR__ . "/CertificateProcessing.Module.View.php";

class CertificateProcessing_Module_Controller
{
    public static function dispatch($request)
    {
        // T12: Admin Approves Certificate Request
        Route::post('api/certificate-processing/approve', function ($request) {
            $data = CertificateProcessing_Module_Model::approve($request['body']);
            return CertificateProcessing_Module_View::formatResponse($data);
        });

        // T12b: Admin Disapproves / Rejects
        Route::post('api/certificate-processing/reject', function ($request) {
            $data = CertificateProcessing_Module_Model::reject($request['body']);
            return CertificateProcessing_Module_View::formatResponse($data);
        });

        // T13: Print Certificate (returns HTML for print)
        Route::get('api/certificate-processing/print', function ($request) {
            $params = $request['params'] ?? array();

            // If no params in URL, try to get from raw body
            if (empty($params)) {
                $rawInput = file_get_contents('php://input');
                if (!empty($rawInput)) {
                    $params = json_decode($rawInput, true);
                }
            }

            $data = CertificateProcessing_Module_Model::printCertificate($params);
            return CertificateProcessing_Module_View::printHtml($data);
        });

        // T14: Mark Request as Completed
        Route::post('api/certificate-processing/complete', function ($request) {
            $data = CertificateProcessing_Module_Model::complete($request['body']);
            return CertificateProcessing_Module_View::formatResponse($data);
        });

        // T15: Generate Yearly Report
        Route::get('api/certificate-processing/report/yearly', function ($request) {
            $queryParams = $request['query'] ?? $_GET ?? array();
            $data = CertificateProcessing_Module_Model::yearlyReport($queryParams);
            return CertificateProcessing_Module_View::formatResponse($data);
        });

        // Admin dashboard counts (pending requests, pending verifications)
        Route::get('api/certificate-processing/dashboard', function ($request) {
            $data = CertificateProcessing_Module_Model::dashboardCounts();
            return CertificateProcessing_Module_View::formatResponse($data);
        });

        // List pending certificate requests
        Route::get('api/certificate-processing/pending', function ($request) {
            $data = CertificateProcessing_Module_Model::pendingList();
            return CertificateProcessing_Module_View::formatResponse($data);
        });

        Route::get('api/certificate-processing/requests', function ($request) {
            $data = CertificateProcessing_Module_Model::staffRequests($request['params'] ?? array());
            return CertificateProcessing_Module_View::formatResponse($data);
        });
    }
}
