<?php

/**
 * Safecharge Safecharge api payment cc response model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Response_Payment_Cc
    extends Safecharge_Safecharge_Model_Api_Response_Payment_Abstract
{
    /**
     * @var string
     */
    protected $orderId;

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
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_Cc
     */
    protected function processResponseData()
    {
        $body = $this->curl->getBody();

        $this->orderId = $body['orderId'];
        $this->transactionId = $body['transactionId'];
        $this->authCode = $body['authCode'];

        return $this;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_Cc
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     */
    protected function updateTransaction()
    {
        parent::updateTransaction();

        $this->orderPayment->setAdditionalInformation(
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_ID,
            $this->transactionId
        );
        $this->orderPayment->setAdditionalInformation(
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_REQUEST_ID,
            $this->requestEntity->getRequestId()
        );
        $this->orderPayment->setAdditionalInformation(
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_ORDER_ID,
            $this->orderId
        );
        $this->orderPayment->setAdditionalInformation(
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_AUTH_CODE_KEY,
            $this->authCode
        );
        $this->orderPayment->setAdditionalInformation(
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_CARD_NUMBER,
            'XXXX-' . $this->orderPayment->getCcLast4()
        );
        $this->orderPayment->setAdditionalInformation(
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_CARD_TYPE,
            $this->orderPayment->getCcType()
        );

        $isSettled = false;
        if ($this->config->getPaymentAction() === Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE) {
            $isSettled = true;
        }

        $this->orderPayment
            ->setTransactionId($this->transactionId)
            ->setIsTransactionClosed($isSettled ? 1 : 0);

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
                'orderId',
                'transactionId',
                'authCode',
                'transactionStatus',
            )
        );
    }
}
