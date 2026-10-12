<?php

// This class manages all HTTP routing for the application
class Route
{
    // Static array to store all registered routes (each route is a method + URI + callback)
    private static $routes = [];

    // Generic method to add a route to the routing table
    // $method: HTTP verb (GET, POST, etc.)
    // $uri: URL pattern (e.g., '/api/auth/signup' or '/user/{id}')
    // $callback: function to run when route matches
    public static function add(string $method, string $uri, callable $callback)
    {
        // Store the route as an associative array inside the static $routes property
        self::$routes[] = [
            'method'   => strtoupper($method),   // Normalize to uppercase (GET, POST...)
            'uri'      => $uri,                  // The pattern to match
            'callback' => $callback              // The function to call
        ];
    }

    // Convenience method: register a GET route
    public static function get(string $uri, callable $callback)
    {
        self::add('GET', $uri, $callback);
    }
    // Convenience method: register a POST route
    public static function post(string $uri, callable $callback)
    {
        self::add('POST', $uri, $callback);
    }
    // Convenience method: register a PUT route
    public static function put(string $uri, callable $callback)
    {
        self::add('PUT', $uri, $callback);
    }
    // Convenience method: register a DELETE route
    public static function delete(string $uri, callable $callback)
    {
        self::add('DELETE', $uri, $callback);
    }
    // Convenience method: register an OPTIONS route (used for CORS preflight)
    public static function options(string $uri, callable $callback)
    {
        self::add('OPTIONS', $uri, $callback);
    }

    // Private helper: sends a response back to the client (usually JSON)
    // $data: can be a string (JSON or HTML) or an array (will be JSON-encoded)
    // $code: HTTP status code (200, 404, 500, etc.)
    private static function sendJson($data, int $code = 200)
    {
        http_response_code($code);   // Set the HTTP status code

        // If the data is a string and starts with '<' (likely HTML), send as HTML
        if (is_string($data) && str_starts_with(trim($data), '<')) {
            header('Content-Type: text/html; charset=utf-8');
            echo $data;
            return;
        }
        // Otherwise, send as JSON
        header('Content-Type: application/json; charset=utf-8');
        // If $data is already a string, send it as is; otherwise encode array to JSON
        echo is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // Analyzes the current request URI to extract the base directory, module name, and event path
    // This is used for modular routing (e.g., /api/authentication/signup)
    public static function RequestUri()
    {
        // Determine if the request is HTTPS or HTTP
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $https . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');   // Full host (e.g., https://example.com)
        $uri = $_SERVER['REQUEST_URI'] ?? '/';                              // Raw URI from the server

        // Extract the path part (without query string)
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        // Split the path by slashes, remove empty segments, and re-index array
        $segments = array_values(array_filter(explode('/', $path)));

        // Default values
        $dir = "";
        $module = "";
        $event = "";

        // Parse the segments depending on whether the first part is 'api'
        if (count($segments) > 0) {
            if ($segments[0] === 'api' && count($segments) > 1) {
                // Example: /api/auth/login  → dir='api', module='auth', event='/login'
                $dir = 'api';
                $module = $segments[1];
                $event = count($segments) > 2 ? "/" . implode("/", array_slice($segments, 2)) : "";
            } else {
                // Example: /bescms/api/auth/login → dir='bescms', module='auth', event='/login'
                $dir = $segments[0] ?? "";
                $module = $segments[2] ?? "";
                $event = count($segments) > 3 ? "/" . implode("/", array_slice($segments, 3)) : "";
            }
        }

        // Return the parsed components
        return [
            "Host"   => $host,
            "DIR"    => $dir,
            "Module" => $module,
            "Event"  => $event
        ];
    }

    // Forwards the request to another controller file (this is a helper for modular routing)
    // $file: the base name of the controller file (without .Module.Controller.php)
    // $callback: a function that receives the request and should call the Controller's dispatch
    public static function forward(string $file, callable $callback)
    {
        // Include the controller file (e.g., Authentication.Module.Controller.php)
        require_once $file . ".Module.Controller.php";

        // Read the raw POST/PUT body (for JSON input)
        $rawBody = file_get_contents('php://input');
        $jsonBody = json_decode($rawBody, true);
        // If it's valid JSON, use decoded array; otherwise keep raw string
        $body = ($jsonBody === null && $rawBody !== '') ? $rawBody : $jsonBody;

        // Build a request object similar to what dispatch() would provide
        $req = [
            'method'       => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'uri'          => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/',
            'params'       => $_REQUEST,          // Combined GET and POST parameters
            'body'         => $body,
            'route_params' => []                  // No dynamic route params here
        ];

        // Call the provided callback (which usually calls the Controller's dispatch method)
        $callback($req);
        // After the callback registers routes, run the dispatch process
        return self::dispatch();
    }

    // The main method: matches the current request against registered routes and executes the callback
    public static function dispatch()
    {
        try {
            // Get the HTTP method and request URI from the server
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $requestUri = parse_url($uri, PHP_URL_PATH) ?? '/';

            // Remove the script name (like /index.php) from the URI if present
            $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '') ?: '';
            if (basename($scriptName) === 'backend') {
                $scriptName = dirname($scriptName);
            }
            if ($scriptName !== '/' && $scriptName !== '' && strpos($requestUri, $scriptName) === 0) {
                $requestUri = substr($requestUri, strlen($scriptName));
            }
            // Normalize the URI: ensure it starts with a slash and remove trailing slashes
            $requestUri = '/' . trim($requestUri, '/');

            // Handle preflight OPTIONS request for CORS (Cross-Origin Resource Sharing)
            if ($requestMethod === 'OPTIONS') {
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization');
                http_response_code(204);   // No content
                exit;
            }

            // Read the request body (for POST, PUT, etc.)
            $rawBody = file_get_contents('php://input');
            $jsonBody = json_decode($rawBody, true);
            $body = ($jsonBody === null && $rawBody !== '') ? $rawBody : $jsonBody;

            // Loop through all registered routes to find a match
            foreach (self::$routes as $route) {
                // Skip if HTTP method doesn't match
                if ($route['method'] !== $requestMethod) continue;

                // Normalize the route URI (start with /, remove trailing slashes)
                $routeUri = '/' . trim($route['uri'], '/');

                // Check if the route URI contains dynamic parameters like {id}
                if (strpos($routeUri, '{') !== false) {
                    // Convert {param} to a regular expression pattern: (?P<param>[^/]+)
                    $pattern = preg_replace_callback(
                        '#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#',
                        fn($m) => '(?P<' . $m[1] . '>[^/]+)',
                        $routeUri
                    );
                    $pattern = '#^' . $pattern . '$#';

                    // Test if the request URI matches this pattern
                    if (preg_match($pattern, $requestUri, $matches)) {
                        // Extract named parameters (e.g., id => 123)
                        $routeParams = [];
                        foreach ($matches as $k => $v) {
                            if (!is_int($k)) $routeParams[$k] = $v;
                        }

                        // Build the request object
                        $req = [
                            'method'       => $requestMethod,
                            'uri'          => $requestUri,
                            'params'       => $_REQUEST,
                            'body'         => $body,
                            'route_params' => $routeParams
                        ];

                        // Execute the callback and send the response
                        $result = call_user_func($route['callback'], $req);
                        return self::sendJson($result);
                    }
                } else {
                    // Static route (no dynamic parameters) – simple string comparison
                    // Normalize by stripping trailing slashes for comparison
                    if ($routeUri === $requestUri || rtrim($routeUri, '/') === rtrim($requestUri, '/')) {
                        $req = [
                            'method'       => $requestMethod,
                            'uri'          => $requestUri,
                            'params'       => $_REQUEST,
                            'body'         => $body,
                            'route_params' => []
                        ];

                        $result = call_user_func($route['callback'], $req);
                        return self::sendJson($result);
                    }
                }
            }

            // If no route matched, return 404 Not Found
            self::sendJson(['error' => 'Route not found', 'requested' => $requestUri], 404);
        } catch (\Throwable $e) {
            // Catch any exception, log it (using external error handler), and return 500
            Error_Handler::log($e);
            self::sendJson(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }
}
