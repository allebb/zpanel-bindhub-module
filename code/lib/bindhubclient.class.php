<?php

/**
 * A PHP API client for BindHub.com written by Bobby Allen, 11/01/2013.
 */
class BindHubClient {

    private $version = '1.0';
    private $user;
    private $key;
    private $bindhubapiuri = 'https://www.bindhub.com/api/';
    private $useragent = 'ZPanelX AutoUpdater v1.0';
    private $ssl_verify;
    private $request_uri;
    private $request_type;
    private $request_data;
    private $repsonse_data;
    private $response_code = 0;
    private $proxy_host = null;
    private $proxy_port;
    private $proxy_user;
    private $proxy_pass;
    private $api_error = null;

    /**
     * Initalise the API client with the user's username and key and check dependencies.
     * @param type $init_data
     */
    public function __construct(array $init_data) {
        $this->user = $init_data['user'];
        $this->key = $init_data['key'];
        $this->ssl_verify = true;
        if (!in_array('curl', get_loaded_extensions()))
            die('BindHubAPIClient - Requires the PHP cURL library installed!');
        if (!in_array('openssl', get_loaded_extensions()))
            die('BindHubAPIClient - Requires the PHP OpenSSL library installed!');
    }

    /**
     * Enable of disable SSL verification (eg. when set to 'false' will not complain about SSL self signed certificates etc.)
     * @param bool $boolean Ensure that SSL verification is carried out.
     */
    public function sslverify(bool $boolean) {
        $this->ssl_verify = $boolean;
    }

    /**
     * Configure the use of a proxy server.
     * @param string $host The hostname or IP address of the proxy server.
     * @param int $port The port number to connect to (eg. 8080)
     * @param string $user The username to use to connect with.
     * @param string $pass The password to use to connect with.
     */
    public function useproxy($host, $port, $user, $pass) {
        $this->proxy_host = $host;
        $this->proxy_port = $port;
        $this->proxy_user = $user;
        $this->proxy_pass = $pass;
    }

    /**
     * Builds the resource request.
     * @param string $type HTTP request type eg. 'GET', 'POST' etc.
     * @param string $uri The URI to the API resource eg. 'record/example.zphub.com' or 'ip' (without the format extention!)
     * @param array $data Array of request parameters.
     */
    public function request($type, $uri, $data = null) {
        $this->repsonse_data = null; // Reset the request data.
        $this->api_error = null; // Reset the api error data.
        $this->request_uri = $uri;
        $this->request_type = $type;
        # Build the API request body.
        if ($data) {
            $iterator = 'user=' . $this->user . '&key=' . $this->key . '&';
            foreach ($data as $param => $value) {
                $iterator .= $param . '=' . $value . '&';
            }
            $post_body = rtrim($iterator, '&');
        } else {
            $post_body = 'user=' . $iterator = $this->user . '&key=' . $this->key;
        }

        # Lets initiate a cURL handle.
        $ch = curl_init();

        if ($this->request_type == 'GET') {
            # No future required options as is a simple 'GET' request.
        }
        if ($this->request_type == 'POST') {
            # Set the URL and POST data
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
        }

        # If a proxy host has been specified lets configure cURL to use it!
        if ($this->proxy_host) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy_host);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy_port);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxy_user . ':' . $this->proxy_pass);
        }

        # Execute the request!
        curl_setopt($ch, CURLOPT_URL, $this->bindhubapiuri . $this->request_uri . '.json');
        curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $this->repsonse_data = curl_exec($ch);
        $this->response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        # If the API has given an error message, we store it now so it can be called upon if required.
        if (isset($this->response_as_object()->error))
            $this->api_error = $this->response_as_object()->error;

        # Close connection
        curl_close($ch);
    }

    /**
     * Returns the response as RAW (as is data)
     * @return string The response data as retrieved from the API
     */
    public function response_as_raw() {
        return $this->repsonse_data;
    }

    /**
     * Returns the response as an object.
     * @return object The response data as retrieved from the API
     */
    public function response_as_object() {
        return json_decode($this->repsonse_data, false);
    }

    /**
     * Returns the repsonse as an associated array.
     * @return array The response data as an associated array.
     */
    public function response_as_array() {
        return json_decode($this->repsonse_data, true);
    }

    /**
     * Returns the HTTP status code.
     * @return int The HTTP status code.
     */
    public function response_code() {
        return $this->response_code;
    }

    /**
     * Returns the API error message as reported by the API server.
     * @return type
     */
    public function api_error_message() {
        return $this->api_error;
    }

    /**
     * A quick method for updating a given host with a new IP address.
     * @param string $record The FQDN of the record you wish to update.
     * @param string $target A valid IPv4 or IPv6 IP address of which to set the record with.
     * @return boolean Is request was successfull of not (use $object->api_error_message to see what the error is!)
     */
    public function update_ip_address($record, $target) {
        $this->request('POST', 'record/update', array(
            'record' => $record, // We set the record we want to update here!
            'target' => $target, // Then we set the IP address that we want to change the record to point to!
        ));
        if ($this->api_error_message()) // If the api_error_message is set then we know we have a problem!
            return false;
        return true;
    }

    /**
     * A quick method for getting all records on the user's account.
     * @return boolean Is request was successfull of not (use $object->api_error_message to see what the error is!)
     */
    public function get_all_records() {
        $this->request('POST', 'record');
        if ($this->api_error_message())
            return false;
        return true;
    }

    /**
     * Return the details of a single record (to reduce bandwidth).
     * @param string $name The FQDN of the record you wish to return the details of.
     * @return boolean Is request was successfull of not (use $object->api_error_message to see what the error is!)
     */
    public function get_record_by_name($name) {
        $this->request('POST', 'record/' . $name);
        if ($this->api_error_message())
            return false;
        return true;
    }

    /**
     * Returns the public IP address of the current machine.
     * @return boolean
     */
    public function get_public_ip_address() {
        $this->request('GET', 'ip');
        if ($this->api_error_message())
            return false;
        return $this->response_as_object()->address->public;
    }

}

?>
