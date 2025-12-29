<?php

use dokuwiki\Extension\Plugin;
use dokuwiki\HTTP\DokuHTTPClient;
use dokuwiki\Logger;

/**
 * DokuWiki Plugin authud (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Lukasz Biegaj <ud@x93.org>
 */
class helper_plugin_authud extends Plugin
{
    /**
     * @var array|null|false In-memory cache for session validation result 
     */
    protected $sessionCache = null;

    /**
     * Validate session against external API
     *
     * Makes a POST request to the configured endpoint with session cookie
     * and API key. Results are cached in memory for the request lifecycle.
     *
     * @return array|false User data array on success, false on failure
     */
    public function validateSession()
    {
        // Return cached result if available (only one API call per request)
        if ($this->sessionCache !== null) {
            Logger::debug('authud', 'validateSession: returning cached result');
            return $this->sessionCache;
        }

        // Get configuration values
        $endpoint = $this->getConf('endpoint');
        $apiKey = $this->getConf('apikey');
        $cookieName = $this->getConf('cookiename');

        // Check if session cookie exists
        if (!isset($_COOKIE[$cookieName]) || empty($_COOKIE[$cookieName])) {
            Logger::debug('authud', 'validateSession: session cookie not found');
            $this->sessionCache = false;
            return false;
        }

        $sessionId = $_COOKIE[$cookieName];

        // Make HTTP request
        $http = new DokuHTTPClient();
        $http->timeout = 10; // 10 second timeout for validation
        $http->headers['X-API-Key'] = $apiKey;
        $http->headers['Content-Type'] = 'application/json';
        $http->headers['Accept'] = 'application/json';
        $http->headers['X-Session-ID'] = $sessionId;
        $response = $http->post($endpoint, []);

        // Handle HTTP errors
        if ($response === false) {
            Logger::error('authud', 'validateSession: HTTP request failed:' . $http->error . "\nResponse:" . base64_encode($http->resp_body));
            $this->sessionCache = false;
            return false;
        }

        if ((int)$http->status !== 200) {
            Logger::error('authud', 'validateSession: API returned HTTP ' . $http->status . "\nResponse:" . base64_encode($http->resp_body));
            $this->sessionCache = false;
            return false;
        }

        // Parse JSON response
        $data = json_decode($response, true);

        if ($data === null || json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('authud', 'validateSession: invalid JSON response - ' . json_last_error_msg());
            $this->sessionCache = false;
            return false;
        }

        // Validate response structure and valid flag
        if (!isset($data['valid']) || !(bool)$data['valid']) {
            Logger::debug('authud', 'validateSession: session not valid or invalid flag');
            $this->sessionCache = false;
            return false;
        }

        // Validate required user data fields
        $requiredFields = ['user_id', 'user_email'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                Logger::error('authud', "validateSession: missing required field: $field");
                $this->sessionCache = false;
                return false;
            }
        }

        // Build user data array
        $userData = [
            'valid' => $data['valid'],
            'user_id' => $data['user_id'],
            'user_email' => $data['user_email'],
        ];

        Logger::debug('authud', 'validateSession: session validated for user ' . $userData['user_id']);

        // Cache the result
        $this->sessionCache = $userData;

        return $userData;
    }
}
