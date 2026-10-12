<?php

// This class handles errors and exceptions (catches them, logs them, returns user-friendly messages)
class Error_Handler
{
    // Public static method: wraps any callback (function) in a try-catch block.
    // $callback: the code you want to run (like a function or closure).
    // $Page_Error: optional string to identify where the error happened (e.g., "Database Module").
    public static function handle(callable $callback, $Page_Error = "")
    {
        try {
            // Execute the provided callback (the main code that might throw an error)
            return $callback();
        } catch (\Throwable $e) {
            // Catch ANY error or exception (Throwable is the base interface for all errors/exceptions in PHP 7+)

            // Build an error information array (safe to return to the user)
            $errorInfo = array(
                "Status"  => "Error: " . $e->getMessage() . " in " . $Page_Error
            );

            // Log the full exception details to a file (for developers)
            self::log($e);

            // Return the user-friendly error info (so the caller can display it, e.g., as JSON)
            return $errorInfo;
        }
    }

    // Public static method: logs an error message or exception to a file.
    // $input: can be an Exception/Throwable object or a plain string.
    public static function log($input)
    {
        // Check if the input is an exception/error object
        if ($input instanceof \Throwable) {
            // Format a detailed log message with timestamp, message, file, line, and stack trace
            $logMessage = sprintf(
                "[%s] %s in %s on line %d\nStack trace:\n%s\n\n",
                date('Y-m-d H:i:s'),           // Current date and time
                $input->getMessage(),           // The error message
                $input->getFile(),              // The file where the error occurred
                $input->getLine(),              // The line number where the error occurred
                $input->getTraceAsString()      // Full stack trace (function call history)
            );
        } else {
            // If it's a plain string, just log the string with a timestamp
            $logMessage = sprintf("[%s] %s\n\n", date('Y-m-d H:i:s'), (string)$input);
        }

        // Append the log message to a file named 'error.log' located in the same directory as this script
        // FILE_APPEND means add to the end of the file, not overwrite it
        file_put_contents(__DIR__ . '/error.log', $logMessage, FILE_APPEND);
    }

    // Private static method: a convenience wrapper that calls the public log() method.
    // This is not used anywhere else in the class, but it's defined for potential internal use.
    private static function logError(\Throwable $e)
    {
        self::log($e);
    }
}
