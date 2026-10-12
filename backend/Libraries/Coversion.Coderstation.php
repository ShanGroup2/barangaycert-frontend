<?php

// This class helps convert data between different formats: arrays, objects, JSON strings, etc.
class Data_Coversion
{

    // Public static method: converts any data (array, object, JSON string) into a plain PHP array.
    // Returns an array with numeric keys (starting from 0) because of array_values().
    public static function toArray($data)
    {
        // If the input is already an array, return it re-indexed (values only, keys become 0,1,2...)
        if (is_array($data)) {
            return array_values($data);
        }

        // If the input is an object, first convert it to an associative array (using JSON trick),
        // then re-index and return.
        if (is_object($data)) {
            // Step 1: json_encode($data) turns object into JSON string.
            // Step 2: json_decode(..., true) turns JSON string into associative array.
            // Step 3: array_values() removes original keys (property names) and makes numeric.
            return array_values(json_decode(json_encode($data), true));
        }

        // If the input is a string, try to interpret it as a JSON string.
        if (is_string($data)) {
            // Attempt to decode JSON into an associative array.
            $decoded = json_decode($data, true);
            // Check if decoding succeeded (no JSON error) and result is an array.
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Return the decoded array, re-indexed.
                return array_values($decoded);
            }

            // If the string is not valid JSON, fallback: wrap the string in an array.
            return [$data];
        }

        // For any other data type (int, float, bool, null), return an empty array as fallback.
        return [];
    }



    // Public static method: converts any data (array, object, string, etc.) into a JSON string.
    public static function toJson($data)
    {
        // If the input is already a string, decide whether it's a JSON string or plain text.
        if (is_string($data)) {
            // Try to decode it as JSON to see if it's already valid JSON.
            $decoded = json_decode($data, true);

            // If decoding succeeded (no error), then $data is a valid JSON string.
            if (json_last_error() === JSON_ERROR_NONE) {
                // Re-encode it to ensure standard formatting (e.g., no extra spaces).
                return json_encode($decoded);
            }

            // If decoding failed, treat $data as a plain string and encode it as JSON.
            return json_encode($data);
        }

        // If the input is an object, convert it to an associative array using get_object_vars(),
        // then encode that array to JSON.
        if (is_object($data)) {
            // get_object_vars($data) returns an array of the object's public properties.
            return json_encode(get_object_vars($data));
        }

        // If the input is an array, directly encode it to JSON.
        if (is_array($data)) {
            return json_encode($data);
        }

        // For any other data type (int, float, bool, null), let json_encode handle it.
        return json_encode($data);
    }
}
