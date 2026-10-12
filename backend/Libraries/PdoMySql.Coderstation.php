<?php

// This class provides database helper methods using PDO (MySQL)
class PdoMySql
{

    //-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // Private helper method: opens a database connection and returns the PDO object
    private static function OpenDatabase($DBase)
    {
        // Set PDO attributes (options) for the connection
        $Options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,          // Fetch results as objects by default
            PDO::ATTR_EMULATE_PREPARES   => false                    // Use native prepared statements (more secure)
        ];

        // Create a new PDO connection using the database config array
        // $DBase should contain: Host, Port, DBName, Username, Password
        $Connection = new PDO(
            "mysql:host=" .  $DBase["Host"] .
                ";port=" .  $DBase["Port"] .
                ";dbname=" .  $DBase["DBName"] .
                ";charset=utf8mb4;",
            $DBase["Username"],
            $DBase["Password"],
            $Options
        );
        return $Connection;
    }


    //-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // Public method: creates a database if it doesn't exist
    public static function CreateDatabase($DBase)
    {
        // Set a custom error handler to convert PHP errors to exceptions
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        });

        $Options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false
        ];

        try {
            // Connect without specifying a database name (to create a new DB)
            $Connection = new PDO(
                "mysql:host=" . $DBase["Host"] .
                    ";port=" . $DBase["Port"] .
                    ";charset=utf8mb4;",
                $DBase["Username"],
                $DBase["Password"],
                $Options
            );
            // Execute SQL to create the database if it doesn't exist
            $Connection->exec("CREATE DATABASE IF NOT EXISTS " . $DBase["DBName"]);
            // Return success message as JSON
            return json_encode(array("Status" => $DBase["DBName"] . " Database is successfully created."), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            // Log any database exception using a custom error handler
            Error_Handler::log($e);
            return false;
        }
    }

    //------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // Public method: creates a table if it doesn't exist using a full CREATE TABLE SQL statement
    public static function CreateDataTable($DBase, $DataTable)
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        });

        $Options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false
        ];

        try {
            $Connection = Self::OpenDatabase($DBase);   // Get a connection to the specific database
            $Connection->exec("CREATE TABLE IF NOT EXISTS " . $DataTable);  // Execute the table creation SQL
            return json_encode(array("Status" => "Table is successfully created."), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $Exception) {
            Error_Handler::log($Exception);
            return json_encode(array("Status" => "Error: " . $Exception->getMessage()), JSON_UNESCAPED_UNICODE);
        }
    }
    //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // Public method: executes any DDL query (e.g., ALTER, DROP, CREATE) and returns a JSON status
    public static function ExecuteDDL($DBase, $Query)
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        });

        $Options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false
        ];

        try {
            $Connection = Self::OpenDatabase($DBase);
            $Connection->exec($Query);   // exec() is used for queries that don't return result sets
            return json_encode(array("Status" => "Query is successfully executed."), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $Exception) {
            Error_Handler::log($Exception);
            return json_encode(array("Status" => "Error: " . $Exception->getMessage()), JSON_UNESCAPED_UNICODE);
        }
    }
    //-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // Public method: executes a DML query (INSERT, UPDATE, DELETE) that doesn't return rows.
    // Returns the number of affected rows (or JSON error on failure).
    public static function ExecuteDML_NonQuery($DBase, $Query, $Parameter = null)
    {
        $RowCount = 0;

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        });

        try {
            $Connection = Self::OpenDatabase($DBase);
            $Statement = $Connection->prepare($Query);   // Prepare the SQL statement

            // Execute with or without parameters
            if ($Parameter == null) {
                $Statement->execute();
            } else {
                $Statement->execute($Parameter);
            }

            $RowCount = $Statement->rowCount();   // Get number of affected rows
        } catch (PDOException $Exception) {
            Error_Handler::log($Exception);
            return json_encode(array("Status" => "Error: " . $Exception->getMessage()), JSON_UNESCAPED_UNICODE);
        }

        return $RowCount;
    }
    //------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // Public method: executes a query that returns rows (SELECT) and returns the result as JSON string.
    public static function ExecuteDML_Query($DBase, $Query, $Parameter = null)
    {
        $Result = "";

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        });

        try {
            $Connection = Self::OpenDatabase($DBase);
            $Statement = $Connection->prepare($Query);

            if ($Parameter == null) {
                $Statement->execute();
            } else {
                $Statement->execute($Parameter);
            }

            // Fetch all rows as associative arrays and convert to JSON
            $Result = json_encode($Statement->fetchall(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
        } catch (PDOException $Exception) {
            Error_Handler::log($Exception);
            return json_encode(array("Status" => "Error: " . $Exception->getMessage()), JSON_UNESCAPED_UNICODE);
        }

        // Clean up the JSON: remove escaped backslashes and quotes around nested objects
        $Result = str_replace("\\", "", $Result);
        $Result = str_replace("\"{", "{", $Result);
        $Result = str_replace("}\"", "}", $Result);

        return $Result;
    }
    //-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
}
