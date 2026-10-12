<?php
// Include the data conversion library (note the typo in 'Coversion' – should be 'Conversion')
require_once __DIR__ . '/../../Libraries/Coversion.Coderstation.php';

// This class is responsible for formatting the output (the "View" in MVC)
// It takes raw data from the Model and turns it into a response the client can understand
class Authentication_Module_View
{
    // Public static method: formats the response (usually converts array to JSON)
    public static function formatResponse($param)
    {
        // Convert empty arrays in Data field to null
        if (isset($param['Data']) && is_array($param['Data']) && empty($param['Data'])) {
            $param['Data'] = null;
        }
        // Call the toJson method from the Data_Coversion library
        // This converts a PHP array (like ["Status"=>"Success", "Message"=>"OK"]) into a JSON string
        return Data_Coversion::toJson($param);
    }
}
