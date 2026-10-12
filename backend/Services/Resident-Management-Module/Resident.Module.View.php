<?php
require_once __DIR__ . '/../../Libraries/Coversion.Coderstation.php';

class Resident_Module_View
{
    public static function formatResponse($param)
    {
        // Convert empty arrays in Data field to null
        if (isset($param['Data']) && is_array($param['Data']) && empty($param['Data'])) {
            $param['Data'] = null;
        }
        return Data_Coversion::toJson($param);
    }
}
