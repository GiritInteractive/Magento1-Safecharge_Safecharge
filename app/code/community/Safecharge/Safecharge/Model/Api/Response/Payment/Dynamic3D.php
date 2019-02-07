<?php

/**
 * Safecharge Safecharge api payment dynamic 3d response model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Response_Payment_Dynamic3D
    extends Safecharge_Safecharge_Model_Api_Response_Payment_Abstract
{
    /**
     * @var int
     */
    protected $transactionId;

    /**
     * @var int
     */
    protected $threeDFlow;

    /**
     * @var string|null
     */
    protected $acsUrl;

    /**
     * @var int
     */
    protected $orderId;

    /**
     * @var string
     */
    protected $paReq;

    /**
     * @var string|null
     */
    protected $userPaymentOptionId;

    /**
     * @var string
     */
    protected $authCode;

    /**
     * @return string
     */
    protected function getResponseMethod()
    {
        return self::METHOD_DYNAMIC_3D;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_Dynamic3D
     */
    protected function processResponseData()
    {
        $body = $this->curl->getBody();

        $this->transactionId = $body['transactionId'];
        $this->threeDFlow = $body['threeDFlow'];
        $this->acsUrl = !empty($body['acsUrl']) ? $body['acsUrl'] : null;
        $this->orderId = $body['orderId'];
        $this->paReq = !empty($body['paRequest']) ? $body['paRequest'] : null;
        $this->userPaymentOptionId = !empty($body['userPaymentOptionId']) ? $body['userPaymentOptionId'] : null;
        $this->authCode = $body['authCode'];

        return $this;
    }

    /**
     * {@inheritdoc}
     *
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
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_Dynamic3D
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     */
    protected function updateTransaction()
    {
        parent::updateTransaction();

        $this->orderPayment
            ->setTransactionId($this->transactionId)
            ->setIsTransactionPending(false)
            ->setIsTransactionClosed(0);

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

        if ($this->authCode) {
            $this->orderPayment->setAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::TRANSACTION_AUTH_CODE_KEY,
                $this->authCode
            );
        }

        if ($this->orderPayment->getCcLast4()) {
            $this->orderPayment->setAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::TRANSACTION_CARD_NUMBER,
                'XXXX-' . $this->orderPayment->getCcLast4()
            );
        }

        if ($this->orderPayment->getCcType()) {
            $this->orderPayment->setAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::TRANSACTION_CARD_TYPE,
                $this->orderPayment->getCcType()
            );
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getUserPaymentOptionId()
    {
        return $this->userPaymentOptionId;
    }

    /**
     * @return int
     */
    public function getThreeDFlow()
    {
        return $this->threeDFlow;
    }

    /**
     * @return string
     */
    public function getAscUrl()
    {
        return $this->acsUrl;
    }

    /**
     * @return string
     */
    public function getPaReq()
    {
        return $this->paReq;
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return array_merge_recursive(
            parent::getRequiredResponseDataKeys(),
            array(
                'authCode',
                'orderId',
                'transactionId',
                'threeDFlow',
                'transactionStatus',
            )
        );
    }
}
