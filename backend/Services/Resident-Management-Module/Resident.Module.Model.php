<?php
require_once __DIR__ . '/../../Libraries/PdoMySql.Coderstation.php';
require_once __DIR__ . '/../../Libraries/Coversion.Coderstation.php';
require_once __DIR__ . '/../../Libraries/Session.Coderstation.php';

class Resident_Module_Model
{
    private static function isAdminOrBhw()
    {
        $role = Session::get('role');
        return in_array($role, ['admin', 'bhw'], true);
    }

    private static function isAdmin()
    {
        return Session::get('role') === 'admin';
    }

    private static function requireAdminOrBhw()
    {
        if (!self::isAdminOrBhw()) {
            return array("Status" => "Error", "Message" => "Unauthorized: Only admin or BHW can access this function.");
        }
        return null;
    }

    private static function requireAdmin()
    {
        if (!self::isAdmin()) {
            return array("Status" => "Error", "Message" => "Unauthorized: Only admin can access this function.");
        }
        return null;
    }

    public static function add($param)
    {
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;


        $existingResidentJson = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Resident_GetByIdentity(?,?,?)",
            [
                $param['full_name'] ?? null,
                $param['birthdate'] ?? null,
                $param['contact_number'] ?? null
            ]
        );

        $existingResident = json_decode($existingResidentJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                "Status" => "Error",
                "Message" => "Database response could not be decoded.",
                "Data" => $existingResidentJson
            );
        }

        // If the DB returned an error status (e.g. procedure missing), fall back to inline SELECT
        if (is_array($existingResident) && isset($existingResident['Status'])) {
            $statusMessage = $existingResident['Status'];
            if (stripos($statusMessage, 'does not exist') !== false || stripos($statusMessage, 'Procedure') !== false) {
                $existingResidentJson = PdoMySql::ExecuteDML_Query(
                    Application::$DBase,
                    "SELECT id, full_name, birthdate, contact_number FROM residents WHERE full_name = ? AND birthdate = ? AND contact_number = ? LIMIT 1",
                    [
                        $param['full_name'] ?? null,
                        $param['birthdate'] ?? null,
                        $param['contact_number'] ?? null
                    ]
                );
                $existingResident = json_decode($existingResidentJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return array(
                        "Status" => "Error",
                        "Message" => "Database response could not be decoded.",
                        "Data" => $existingResidentJson
                    );
                }
            } else {
                // propagate other DB errors
                return $existingResident;
            }
        }

        // If a matching resident was found, return an error with the existing record.
        if (is_array($existingResident) && count($existingResident) > 0) {
            $existingRow = $existingResident[0];
            return array(
                "Status" => "Error",
                "Message" => "Resident already exists.",
                "Data" => $existingRow
            );
        }

        // Insert new resident via stored procedure (existing behavior)
        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Resident_Add(?,?,?,?,?,?,?)",
            array(
                $param['full_name'] ?? null,
                $param['birthdate'] ?? null,
                $param['address'] ?? null,
                $param['contact_number'] ?? null,
                $param['gender'] ?? null,
                $param['registered_voter'] ?? null,
                $param['added_by'] ?? null
            )
        );
        $Response = Data_Coversion::toArray($Response);
        $Message = $Response[0]["Record_Status"] ?? "Resident added.";

        // Fetch the added resident data
        $ResidentId = $Response[0]["id"] ?? $Response[0]["resident_id"] ?? null;
        if ($ResidentId) {
            $AddedResident = PdoMySql::ExecuteDML_Query(
                Application::$DBase,
                "CALL Resident_Search(?)",
                array($ResidentId)
            );
            $AddedResident = Data_Coversion::toArray($AddedResident);
            return array("Status" => "Success", "Message" => $Message, "Data" => $AddedResident);
        }

        return array("Status" => "Success", "Message" => $Message, "Data" => $Response);
    }

    public static function edit($param)
    {
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;

        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Resident_Update(?,?,?,?,?,?,?,?)",
            array(
                $param['id'] ?? null,
                $param['full_name'] ?? null,
                $param['birthdate'] ?? null,
                $param['address'] ?? null,
                $param['contact_number'] ?? null,
                $param['gender'] ?? null,
                $param['registered_voter'] ?? null,
                $param['updated_by'] ?? null
            )
        );
        $Response = Data_Coversion::toArray($Response);
        $Message = $Response[0]["Record_Status"] ?? "Resident updated.";

        // Fetch the updated resident data
        $ResidentId = $param['id'] ?? null;
        if ($ResidentId) {
            $UpdatedResident = PdoMySql::ExecuteDML_Query(
                Application::$DBase,
                "CALL Resident_Search(?)",
                array($ResidentId)
            );
            $UpdatedResident = Data_Coversion::toArray($UpdatedResident);
            return array("Status" => "Success", "Message" => $Message, "Data" => $UpdatedResident);
        }

        return array("Status" => "Success", "Message" => $Message);
    }

    public static function delete($param)
    {
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;

        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Resident_Delete(?)",
            Data_Coversion::toArray($param)
        );
        $Response = Data_Coversion::toArray($Response);
        $Message = $Response[0]["Record_Status"] ?? "Resident deleted.";
        return array("Status" => "Success", "Message" => $Message);
    }

    public static function export()
    {
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;

        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Resident_Export()"
        );
        $Response = Data_Coversion::toArray($Response);
        return array("Status" => "Success", "Data" => $Response);
    }

    public static function list()
    {
        $authError = self::requireAdmin();
        if ($authError !== null) return $authError;

        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Resident_Load()"
        );
        $Response = Data_Coversion::toArray($Response);
        return array("Status" => "Success", "Data" => $Response);
    }

    public static function search($param)
    {
        $authError = self::requireAdminOrBhw();
        if ($authError !== null) return $authError;

        $Response = PdoMySql::ExecuteDML_Query(
            Application::$DBase,
            "CALL Resident_Search(?)",
            Data_Coversion::toArray($param)
        );
        $Response = Data_Coversion::toArray($Response);
        return array("Status" => "Success", "Data" => $Response);
    }
}
