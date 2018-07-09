<?php

/**
 * Safecharge Safecharge abstract api curl adapter.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Curl extends Varien_Http_Adapter_Curl
{
    /**
     * @var int
     */
    protected $status;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var string
     */
    protected $body;

    /**
     * @param string $uri
     * @param array  $headers
     * @param array  $params
     *
     * @return void
     * @throws Zend_Http_Exception
     */
    public function post(
        $uri,
        array $headers = array(),
        array $params = array()
    ) {
        $this->write(
            Zend_Http_Client::POST,
            $uri,
            '1.1',
            $headers,
            Zend_Json::encode($params)
        );

        $response = curl_exec($this->_getResource());
        if (strpos($response, '100 Continue') !== false) {
            $tmp = explode("\r\n\r\n", $response, 2);
            $response = $tmp[1];
        }

        $this->status = (int)Zend_Http_Response::extractCode($response);
        $this->headers = Zend_Http_Response::extractHeaders($response);
        $this->body = Zend_Http_Response::extractBody($response);

        try {
            $this->body = Zend_Json::decode($this->body);
        } catch (Zend_Json_Exception $e) {
            // Assume that it is html.
            $this->body = preg_replace(
                '/.*<body[^>]*>|<\/body>.*/si',
                '',
                $this->body
            );
            $this->body = trim(strip_tags($this->body));
        }
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return array|string
     */
    public function getBody()
    {
        return $this->body;
    }
}
