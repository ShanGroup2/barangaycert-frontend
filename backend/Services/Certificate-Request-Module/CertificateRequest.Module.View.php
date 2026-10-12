<?php

/**
 * CertificateRequest.Module.View.php
 * Presentation helper for Certificate Request module responses.
 */
require_once __DIR__ . '/../../Libraries/Coversion.Coderstation.php';

/**
 * CertificateRequest_Module_View
 *
 * Responsible for formatting module responses consistently (JSON).
 */
class CertificateRequest_Module_View
{
    /**
     * Convert an internal response array to JSON for API output.
     *
     * @param mixed $param Response payload (associative array or data)
     * @return string JSON encoded response
     */
    public static function formatResponse($param)
    {
        // Convert empty arrays in Data field to null
        if (isset($param['Data']) && is_array($param['Data']) && empty($param['Data'])) {
            $param['Data'] = null;
        }
        // Use shared conversion helper to ensure consistent output formatting
        return Data_Coversion::toJson($param);
    }
}
