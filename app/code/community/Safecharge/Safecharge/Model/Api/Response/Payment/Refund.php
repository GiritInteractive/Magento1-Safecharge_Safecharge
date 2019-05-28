<?php

/**
 * Safecharge Safecharge api payment refund response model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Response_Payment_Refund
    extends Safecharge_Safecharge_Model_Api_Response_Payment_Abstract
{
    /**
     * @var string
     */
    protected $transactionId;

    /**
     * @var string
     */
    protected $authCode;

    /**
     * @return string
     */
    protected function getResponseMethod()
    {
        return self::METHOD_REFUND;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_Refund
     */
    protected function processResponseData()
    {
        $body = $this->curl->getBody();

        $this->transactionId = $body['transactionId'];
        $this->authCode = $body['authCode'];

        return $this;
    }

    /**
     * @return bool
     */
    protected function getRequestStatus()
    {
        if (parent::getRequestStatus() === false) {
            return false;
        }

        $body = $this->curl->getBody();

        $responseTransactionStatus = strtolower(!empty($body['transactionStatus']) ? $body['transactionStatus'] : '');
        if ($responseTransactionStatus === 'error') {
            return false;
        }
        if ($responseTransactionStatus !== 'approved') {
            return false;
        }
        return true;
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return array_merge_recursive(
            parent::getRequiredResponseDataKeys(),
            array(
                'transactionId',
                'authCode',
                'transactionStatus',
            )
        );
    }

    /**
     * @return bool|string
     */
    protected function getErrorReason()
    {
        $body = $this->curl->getBody();
        if (!empty($body['gwErrorReason'])) {
            return $body['gwErrorReason'];
        }

        return parent::getErrorReason();
    }
}
