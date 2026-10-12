<?php
require_once __DIR__ . "/Libraries/Route.Coderstation.php";
require_once __DIR__ . "/Libraries/Error.Handler.Coderstation.php";
require_once __DIR__ . "/Libraries/PdoMySql.Coderstation.php";
require_once __DIR__ . "/Libraries/Coversion.Coderstation.php";
require_once __DIR__ . "/Libraries/Session.Coderstation.php";
require_once __DIR__ . "/Configuration/Application.Config.php";

error_reporting(E_ALL);

$uriData = Route::RequestUri() ?? [];
$module  = strtoupper($uriData["Module"] ?? "");

$Process = Error_Handler::handle(function () use ($module) {
    if ($module === "AUTH") {
        return Route::forward(__DIR__ . '/Services/Authentication-Module/Authentication', function ($req) {
            return Authentication_Module_Controller::dispatch($req);
        });
    } elseif ($module === "RESIDENT") {
        return Route::forward(__DIR__ . '/Services/Resident-Management-Module/Resident', function ($req) {
            return Resident_Module_Controller::dispatch($req);
        });
    } elseif ($module === "CERTIFICATE-REQUEST") {
        return Route::forward(__DIR__ . '/Services/Certificate-Request-Module/CertificateRequest', function ($req) {
            return CertificateRequest_Module_Controller::dispatch($req);
        });
    } elseif ($module === "CERTIFICATE-PROCESSING") {
        return Route::forward(__DIR__ . '/Services/Certificate-Processing-Module/CertificateProcessing', function ($req) {
            return CertificateProcessing_Module_Controller::dispatch($req);
        });
    }
    Route::dispatch();
}, "Main Controller");

if (is_array($Process) && isset($Process["Status"]) && strpos($Process["Status"], "Error:") !== false) {
    echo $Process["Status"];
}
