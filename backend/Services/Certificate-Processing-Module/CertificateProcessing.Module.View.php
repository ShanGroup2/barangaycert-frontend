<?php
require_once __DIR__ . '/../../Libraries/Coversion.Coderstation.php';

class CertificateProcessing_Module_View
{
    public static function formatResponse($param)
    {
        // Convert empty arrays in Data field to null
        if (isset($param['Data']) && is_array($param['Data']) && empty($param['Data'])) {
            $param['Data'] = null;
        }
        return Data_Coversion::toJson($param);
    }

    public static function printHtml($certificateData)
    {
        if (!$certificateData) {
            return "Certificate data not found.";
        }
        $html = "<html><head><title>Barangay Certificate</title>
                 <style>body { font-family: Arial; margin: 50px; } .cert { border: 1px solid #000; padding: 20px; }</style>
                 </head><body>
                 <div class='cert'>
                 <h2>Republic of the Philippines</h2>
                 <h3>Barangay Polo Certification</h3>
                 <p><strong>Resident:</strong> " . htmlspecialchars($certificateData['full_name']) . "</p>
                 <p><strong>Address:</strong> " . htmlspecialchars($certificateData['address']) . "</p>
                 <p><strong>Certificate Type:</strong> " . htmlspecialchars($certificateData['certificate_type']) . "</p>
                 <p><strong>Purpose:</strong> " . htmlspecialchars($certificateData['purpose']) . "</p>
                 <p><strong>Quantity:</strong> " . $certificateData['quantity'] . "</p>
                 <p><strong>Date Approved:</strong> " . $certificateData['approved_at'] . "</p>
                 <button onclick='window.print();'>Print Certificate</button>
                 </div></body></html>";
        return $html;
    }
}
