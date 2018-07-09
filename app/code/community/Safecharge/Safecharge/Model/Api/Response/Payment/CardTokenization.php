<?php

/**
 * Safecharge Safecharge api payment card tokenization response model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Response_Payment_CardTokenization
    extends Safecharge_Safecharge_Model_Api_Response_Payment_Abstract
{
    /**
     * @var string
     */
    protected $ccTempToken;

    /**
     * @return string
     */
    protected function getResponseMethod()
    {
        return self::METHOD_CARD_TOKENIZATION;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_CardTokenization
     */
    protected function processResponseData()
    {
        $body = $this->curl->getBody();

        $this->ccTempToken = $body['ccTempToken'];

        return $this;
    }

    /**
     * @return int
     */
    public function getCcTempToken()
    {
        return $this->ccTempToken;
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return array_merge_recursive(
            parent::getRequiredResponseDataKeys(),
            array(
                'isVerified',
                'ccTempToken',
            )
        );
    }
}
