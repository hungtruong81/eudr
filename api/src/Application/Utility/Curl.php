<?php

declare(strict_types=1);

namespace App\Application\Utility;

class Curl
{
    /**
     * Universal cURL function for API requests
     * 
     * @param string $url The API endpoint URL
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH)
     * @param array|null $data Request data (for POST, PUT, PATCH)
     * @param array $headers Additional headers
     * @param array $options Additional cURL options
     * @return array Response containing 'success', 'data', 'error', 'http_code'
     */
    public static function request(
        string $url, 
        string $method = 'GET', 
        ?array $data = null, 
        array $headers = [], 
        array $options = []
    ): array {
        // Initialize cURL
        $curl = curl_init();
        
        // Default headers
        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: EUDR-API/1.0'
        ];
        
        // Merge headers
        $allHeaders = array_merge($defaultHeaders, $headers);
        
        // Default cURL options
        $defaultOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_ENCODING => '', // Accept all encodings
        ];
        
        // Set HTTP method and data
        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
                if ($data) {
                    $url .= '?' . http_build_query($data);
                    $defaultOptions[CURLOPT_URL] = $url;
                }
                break;
                
            case 'POST':
                $defaultOptions[CURLOPT_POST] = true;
                if ($data) {
                    // Check if Content-Type is JSON
                    $isJson = false;
                    foreach ($allHeaders as $header) {
                        if (stripos($header, 'Content-Type: application/json') !== false) {
                            $isJson = true;
                            break;
                        }
                    }
                    
                    $defaultOptions[CURLOPT_POSTFIELDS] = $isJson ? json_encode($data) : $data;
                }
                break;
                
            case 'PUT':
                $defaultOptions[CURLOPT_CUSTOMREQUEST] = 'PUT';
                if ($data) {
                    $defaultOptions[CURLOPT_POSTFIELDS] = json_encode($data);
                }
                break;
                
            case 'PATCH':
                $defaultOptions[CURLOPT_CUSTOMREQUEST] = 'PATCH';
                if ($data) {
                    $defaultOptions[CURLOPT_POSTFIELDS] = json_encode($data);
                }
                break;
                
            case 'DELETE':
                $defaultOptions[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                if ($data) {
                    $defaultOptions[CURLOPT_POSTFIELDS] = json_encode($data);
                }
                break;
                
            default:
                return [
                    'success' => false,
                    'error' => 'Unsupported HTTP method: ' . $method,
                    'http_code' => 0,
                    'data' => null
                ];
        }
        
        // Merge with custom options
        $finalOptions = array_replace($defaultOptions, $options);
        
        // Set cURL options
        curl_setopt_array($curl, $finalOptions);
        
        // Execute request
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        $info = curl_getinfo($curl);
        
        curl_close($curl);
        
        // Handle cURL errors
        if ($response === false || !empty($error)) {
            return [
                'success' => false,
                'error' => $error ?: 'cURL request failed',
                'http_code' => $httpCode,
                'data' => null,
                'info' => $info
            ];
        }
        
        // Try to decode JSON response
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If not JSON, return raw response
            $decodedResponse = $response;
        }
        
        // Determine success based on HTTP status code
        $success = $httpCode >= 200 && $httpCode < 300;
        
        return [
            'success' => $success,
            'data' => $decodedResponse,
            'error' => $success ? null : "HTTP Error $httpCode",
            'http_code' => $httpCode,
            'info' => $info
        ];
    }

    /**
     * Simplified GET request
     */
    public static function get(string $url, array $params = [], array $headers = []): array
    {
        return self::request($url, 'GET', $params, $headers);
    }

    /**
     * Simplified POST request
     */
    public static function post(string $url, array $data = [], array $headers = []): array
    {
        return self::request($url, 'POST', $data, $headers);
    }

    /**
     * Simplified PUT request
     */
    public static function put(string $url, array $data = [], array $headers = []): array
    {
        return self::request($url, 'PUT', $data, $headers);
    }

    /**
     * Simplified PATCH request
     */
    public static function patch(string $url, array $data = [], array $headers = []): array
    {
        return self::request($url, 'PATCH', $data, $headers);
    }

    /**
     * Simplified DELETE request
     */
    public static function delete(string $url, array $data = [], array $headers = []): array
    {
        return self::request($url, 'DELETE', $data, $headers);
    }

    /**
     * POST request with form data (multipart/form-data)
     */
    public static function postForm(string $url, array $data = [], array $headers = []): array
    {
        $formHeaders = ['Content-Type: multipart/form-data'];
        $finalHeaders = array_merge($formHeaders, $headers);
        
        return self::request($url, 'POST', $data, $finalHeaders);
    }

    /**
     * POST request with URL-encoded form data
     */
    public static function postUrlEncoded(string $url, array $data = [], array $headers = []): array
    {
        $formHeaders = ['Content-Type: application/x-www-form-urlencoded'];
        $finalHeaders = array_merge($formHeaders, $headers);
        
        // Override the default JSON encoding for URL-encoded data
        $options = [
            CURLOPT_POSTFIELDS => http_build_query($data)
        ];
        
        return self::request($url, 'POST', null, $finalHeaders, $options);
    }

    /**
     * Download file from URL
     */
    public static function downloadFile(string $url, string $savePath, array $headers = []): array
    {
        $file = fopen($savePath, 'w');
        if (!$file) {
            return [
                'success' => false,
                'error' => 'Cannot create file: ' . $savePath,
                'http_code' => 0,
                'data' => null
            ];
        }

        $options = [
            CURLOPT_FILE => $file,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 300, // 5 minutes for file download
        ];
        
        $result = self::request($url, 'GET', null, $headers, $options);
        
        fclose($file);
        
        return $result;
    }

    /**
     * Upload file via POST
     */
    public static function uploadFile(string $url, string $filePath, string $fieldName = 'file', array $data = [], array $headers = []): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => 'File not found: ' . $filePath,
                'http_code' => 0,
                'data' => null
            ];
        }

        $data[$fieldName] = new \CURLFile($filePath);
        
        return self::postForm($url, $data, $headers);
    }

    /**
     * Send JSON request with custom timeout
     */
    public static function json(string $url, string $method, array $data = [], array $headers = [], int $timeout = 30): array
    {
        $jsonHeaders = ['Content-Type: application/json'];
        $finalHeaders = array_merge($jsonHeaders, $headers);
        
        $options = [
            CURLOPT_TIMEOUT => $timeout
        ];
        
        return self::request($url, $method, $data, $finalHeaders, $options);
    }

    /**
     * Send request with custom authentication
     */
    public static function withAuth(string $url, string $method, array $data = [], string $token = '', string $authType = 'Bearer', array $headers = []): array
    {
        $authHeaders = [];
        if (!empty($token)) {
            $authHeaders[] = "Authorization: {$authType} {$token}";
        }
        
        $finalHeaders = array_merge($authHeaders, $headers);
        
        return self::request($url, $method, $data, $finalHeaders);
    }

    /**
     * Send request with basic authentication
     */
    public static function withBasicAuth(string $url, string $method, array $data = [], string $username = '', string $password = '', array $headers = []): array
    {
        $options = [];
        if (!empty($username)) {
            $options[CURLOPT_USERPWD] = $username . ':' . $password;
            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        }
        
        return self::request($url, $method, $data, $headers, $options);
    }

    /**
     * Send request with SSL options disabled (for development only)
     */
    public static function insecure(string $url, string $method = 'GET', array $data = [], array $headers = []): array
    {
        $options = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];
        
        return self::request($url, $method, $data, $headers, $options);
    }

    /**
     * Multiple parallel requests
     */
    public static function parallel(array $requests): array
    {
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $results = [];

        // Initialize all requests
        foreach ($requests as $key => $request) {
            $url = $request['url'] ?? '';
            $method = $request['method'] ?? 'GET';
            $data = $request['data'] ?? null;
            $headers = $request['headers'] ?? [];
            $options = $request['options'] ?? [];

            $curl = curl_init();
            
            // Use same logic as single request
            $defaultHeaders = [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: EUDR-API/1.0'
            ];
            
            $allHeaders = array_merge($defaultHeaders, $headers);
            
            $defaultOptions = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => $allHeaders,
                CURLOPT_ENCODING => '',
            ];

            // Set method and data
            $method = strtoupper($method);
            switch ($method) {
                case 'GET':
                    if ($data) {
                        $url .= '?' . http_build_query($data);
                        $defaultOptions[CURLOPT_URL] = $url;
                    }
                    break;
                case 'POST':
                    $defaultOptions[CURLOPT_POST] = true;
                    if ($data) {
                        $isJson = false;
                        foreach ($allHeaders as $header) {
                            if (stripos($header, 'Content-Type: application/json') !== false) {
                                $isJson = true;
                                break;
                            }
                        }
                        $defaultOptions[CURLOPT_POSTFIELDS] = $isJson ? json_encode($data) : $data;
                    }
                    break;
                case 'PUT':
                case 'PATCH':
                case 'DELETE':
                    $defaultOptions[CURLOPT_CUSTOMREQUEST] = $method;
                    if ($data) {
                        $defaultOptions[CURLOPT_POSTFIELDS] = json_encode($data);
                    }
                    break;
            }

            $finalOptions = array_replace($defaultOptions, $options);
            curl_setopt_array($curl, $finalOptions);
            
            curl_multi_add_handle($multiHandle, $curl);
            $curlHandles[$key] = $curl;
        }

        // Execute all requests
        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        // Collect results
        foreach ($curlHandles as $key => $curl) {
            $response = curl_multi_getcontent($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            $info = curl_getinfo($curl);

            curl_multi_remove_handle($multiHandle, $curl);
            curl_close($curl);

            // Process response same as single request
            if ($response === false || !empty($error)) {
                $results[$key] = [
                    'success' => false,
                    'error' => $error ?: 'cURL request failed',
                    'http_code' => $httpCode,
                    'data' => null,
                    'info' => $info
                ];
            } else {
                $decodedResponse = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $decodedResponse = $response;
                }

                $success = $httpCode >= 200 && $httpCode < 300;
                
                $results[$key] = [
                    'success' => $success,
                    'data' => $decodedResponse,
                    'error' => $success ? null : "HTTP Error $httpCode",
                    'http_code' => $httpCode,
                    'info' => $info
                ];
            }
        }

        curl_multi_close($multiHandle);
        
        return $results;
    }
}
