<?php
// This class manages PHP sessions (persistent user data across page requests)
class Session
{
    // Starts the session if it hasn't been started yet
    public static function start()
    {
        // Check the current session status
        // PHP_SESSION_NONE means no session is active
        if (session_status() === PHP_SESSION_NONE) {
            // Start the session – allows using $_SESSION array
            session_start();
        }
    }

    // Store a value in the session (like saving a variable for later)
    public static function set($key, $value)
    {
        // Make sure the session is started
        self::start();
        // Store the value in the global $_SESSION array under the given key
        $_SESSION[$key] = $value;
    }

    // Retrieve a value from the session
    // $default is returned if the key doesn't exist (optional)
    public static function get($key, $default = null)
    {
        // Ensure session is active
        self::start();
        // Return the value if it exists, otherwise return $default
        // The ?? is the null coalescing operator – returns $_SESSION[$key] if set, else $default
        return $_SESSION[$key] ?? $default;
    }

    // Check if a session key exists (without retrieving its value)
    public static function has($key)
    {
        self::start();
        // isset() returns true if the key exists and is not null
        return isset($_SESSION[$key]);
    }

    // Destroy the entire session (logs the user out, removes all data)
    public static function destroy()
    {
        self::start();
        // Clear the $_SESSION array (remove all stored data)
        $_SESSION = [];
        // Destroy the session on the server (deletes session file)
        session_destroy();
    }
}
