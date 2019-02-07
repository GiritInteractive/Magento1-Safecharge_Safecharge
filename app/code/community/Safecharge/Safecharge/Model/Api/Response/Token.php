<?php

/**
 * Safecharge Safecharge api token response model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Response_Token
    extends Safecharge_Safecharge_Model_Api_Response_Abstract
{
    /**
     * @return string
     */
    protected function getResponseMethod()
    {
        return self::METHOD_SESSION_TOKEN;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->curl->getBody()['sessionToken'];
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return array(
            'sessionToken',
        );
    }
}
