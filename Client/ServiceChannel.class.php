<?php

class ServiceChannel
{
    const TOKEN_EXPIRATION_THRESHOLD = 10;

    private $_applicationId;
    private $_applicationSecret;
    private $_token = null;
    private $_serviceUri;


    public function __construct($applicationId, $applicationSecret, $serviceUri) {
        $this->_applicationId = $applicationId;
        $this->_applicationSecret = $applicationSecret;
        $this->_serviceUri = $this->endsWith($serviceUri, '/') ? $serviceUri : $serviceUri . '/';
    }

    public function get($serviceName, $queryParameters = null) {
        if ($queryParameters != null) {
            $serviceName .= '?' . http_build_query($queryParameters);
        }

        return $this->sendJson('GET', $serviceName, null);
    }

    public function post($serviceName, $json) {
        return $this->sendJson('POST', $serviceName, $json);
    }

    public function delete($serviceName, $queryParameters = null) {
        return $this->sendJson('DELETE', $serviceName, null);
    }

    public function put($serviceName, $queryParameters = null) {
        return $this->sendJson('PUT', $serviceName, null);
    }

    private function sendJson($method, $serviceName, $json) {
        $token = $this->getToken();

        $headers = array(
            sprintf('Authorization: Bearer %s', $token)
        );

        if ($json != null)
            $json = json_encode($json);

        $response = $this->send($method, $serviceName, $headers, $json);

        if ($response->code == 200)
            return $response->body;

        throw new Exception($response->Details);
    }

    private function getToken()
    {
        $token = $this->_token;

        if ($token != null) {
            $now = new DateTime();

            if ($now < $token['expiration'])
                return $token['value'];

            $this->_token = null;
        }

        $headers = array(
            'Content-type: application/x-www-form-urlencoded',
            sprintf('Authorization: Basic %s', base64_encode($this->_applicationId . ':' . $this->_applicationSecret))
        );

        $body = 'grant_type=client_credentials';

        $response = $this->send('POST', 'OAuth/TokenEndpoint', $headers, $body);

        if ($response->code != 200) {
            throw new Exception($response->error_description);
        }

        $value = $response->body->access_token;

        $now = new DateTime();

        $expirationSeconds = $response->body->expires_in - self::TOKEN_EXPIRATION_THRESHOLD;

        $interval = new DateInterval('PT' . $expirationSeconds . 'S');

        $this->_token = array('value' => $value,
            'expiration' => $now->add($interval));

        return $value;
    }

    private function send($method, $serviceName, $headers, $content)
    {
        $url = $this->_serviceUri . $serviceName;

        $session = curl_init($url);

        curl_setopt($session, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);

        if ($content != null)
            curl_setopt($session, CURLOPT_POSTFIELDS, $content);


        curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($session, CURLOPT_HEADER, TRUE);

        $data = curl_exec($session);

        if ($data == FALSE) {
            $error = curl_error($session);

            curl_close($session);

            throw new Exception($error);
        }

        $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);

        $headerSize = curl_getinfo($session, CURLINFO_HEADER_SIZE);

        curl_close($session);

        $body = substr($data, $headerSize);

        $convertedResponse = mb_convert_encoding($body, 'HTML-ENTITIES', "UTF-8");

        $response = json_decode($convertedResponse);

        return  (object) array(
            'code' => $httpCode,
            'body' => $response
        );
    }

    function endsWith($sourceString, $findString) {

        $length = strlen($findString);

        if ($length == 0) {
            return true;
        }

        return (substr($sourceString, -$length) === $findString);
    }
}