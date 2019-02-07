<?php

/**
 * Safecharge Safecharge api payment user payment option response model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Response_Payment_UserPaymentOption
    extends Safecharge_Safecharge_Model_Api_Response_Payment_Abstract
{
    /**
     * @var string
     */
    protected $ccToken;

    /**
     * @return string
     */
    protected function getResponseMethod()
    {
        return self::METHOD_USER_PAYMENT_OPTION;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_UserPaymentOption
     */
    protected function processResponseData()
    {
        $body = $this->curl->getBody();

        $this->ccToken = $body['userPaymentOptionId'];

        return $this;
    }

    /**
     * @return int
     */
    public function getCcToken()
    {
        return $this->ccToken;
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return array_merge_recursive(
            parent::getRequiredResponseDataKeys(),
            array(
                'userPaymentOptionId',
            )
        );
    }
}
