<?php

/**
 * Safecharge Safecharge api payment settle response model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Response_Payment_Settle
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
        return self::METHOD_CC;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_Settle
     */
    protected function processResponseData()
    {
        $body = $this->curl->getBody();

        $this->transactionId = $body['transactionId'];
        $this->authCode = $body['authCode'];

        return $this;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_Settle
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     */
    protected function updateTransaction()
    {
        parent::updateTransaction();

        if ($this->config->getPaymentAction() === Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE) {
            $this->orderPayment->setAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::TRANSACTION_AUTH_CODE_KEY,
                $this->authCode
            );
            $this->orderPayment->setAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::TRANSACTION_ID,
                $this->transactionId
            );
        }

        $this->orderPayment
            ->setParentTransactionId($this->orderPayment->getParentTransactionId())
            ->setTransactionId($this->transactionId)
            ->setIsTransactionClosed(1);

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
        if (strtolower($body['transactionStatus']) === 'error') {
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
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }
}
