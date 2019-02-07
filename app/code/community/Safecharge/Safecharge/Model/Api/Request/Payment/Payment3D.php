<?php

/**
 * Safecharge Safecharge api payment payment 3d request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Request_Payment_Payment3D
    extends Safecharge_Safecharge_Model_Api_Request_Payment_Abstract
{
    /**
     * @var string|null
     */
    protected $userPaymentOptionId;

    /**
     * @var string|null
     */
    protected $cardCvv;

    /**
     * @var string|null
     */
    protected $paResponse;

    /**
     * @return string
     */
    protected function getRequestMethod()
    {
        return Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_PAYMENT_3D;
    }

    /**
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_PAYMENT_3D;
    }

    /**
     * @return array
     * @throws Exception
     * @throws Mage_Core_Exception
     * @throws Mage_Payment_Exception
     */
    protected function getParams()
    {
        /** @var Mage_Sales_Model_Order_Payment $orderPayment */
        $orderPayment = $this->orderPayment;

        /** @var Mage_Sales_Model_Order $order */
        $order = $orderPayment->getOrder();

        if ($this->userPaymentOptionId === null) {
            $this->processCardTokenization();
        }

        $params = array_merge_recursive(
            $this->getOrderData($order),
            $this->getPaymentData(),
            array(
                'orderId' => $orderPayment->getAdditionalInformation(
                    Safecharge_Safecharge_Model_Safecharge::TRANSACTION_ORDER_ID
                ),
                'sessionToken' => $orderPayment->getAdditionalInformation(
                    Safecharge_Safecharge_Model_Safecharge::TRANSACTION_SESSION_TOKEN
                ),
                'transactionType' => $this->getActionType(),
                'amount' => (float)$this->amount,
            ),
            parent::getParams()
        );

        if ($this->paResponse !== null) {
            $params['paResponse'] = $this->paResponse;
        }

        $this->requestEntity
            ->setParentRequestId(
                $orderPayment->getAdditionalInformation(
                    Safecharge_Safecharge_Model_Safecharge::TRANSACTION_REQUEST_ID
                )
            )
            ->setIncrementId($order->getIncrementId())
            ->save();

        return $params;
    }

    /**
     * @return array
     * @throws Mage_Payment_Exception
     * @throws Varien_Exception
     */
    protected function getPaymentData()
    {
        $paymentData = array();

        /** @var Mage_Sales_Model_Order_Payment $orderPayment */
        $orderPayment = $this->orderPayment;

        if ($this->userPaymentOptionId !== null) {
            $paymentData['userPaymentOption'] = array(
                'userPaymentOptionId' => $this->userPaymentOptionId,
                'CVV' => $this->cardCvv,
            );
        } else {
            $ccToken = $orderPayment->getAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::KEY_CC_TOKEN
            );

            /** @var Safecharge_Safecharge_Model_Vault $paymentToken */
            $paymentToken = Mage::getModel('safecharge_safecharge/vault');
            $paymentTokenId = $paymentToken->getIdByPublicHash($ccToken);

            if ($paymentTokenId === false) {
                throw new Mage_Payment_Exception(
                    __('Requested payment token does not exists.')
                );
            }

            $paymentToken->load($paymentTokenId);
            $paymentTokenDetails = $paymentToken->getTokenDetails();
            $paymentTokenDetails = json_decode($paymentTokenDetails, 1);

            $orderPayment
                ->setCcType($paymentTokenDetails['cc_type'])
                ->setCcLast4($paymentTokenDetails['cc_last_4'])
                ->setCcExpMonth($paymentTokenDetails['cc_exp_month'])
                ->setCcExpYear($paymentTokenDetails['cc_exp_year']);

            $paymentData['userPaymentOption'] = array(
                'CVV' => $orderPayment->getCcCid(),
                'userPaymentOptionId' => $paymentToken->getGatewayToken(),
            );
        }

        return $paymentData;
    }

    /**
     * @return array
     */
    protected function getChecksumKeys()
    {
        return array(
            'merchantId',
            'merchantSiteId',
            'clientRequestId',
            'amount',
            'currency',
            'timeStamp',
        );
    }

    /**
     * @param string $userPaymentOptionId
     *
     * @return Safecharge_Safecharge_Model_Api_Request_Payment_Payment3D
     */
    public function setUserPaymentOptionId($userPaymentOptionId)
    {
        $this->userPaymentOptionId = $userPaymentOptionId;

        return $this;
    }

    /**
     * @param string $cardCvv
     *
     * @return Safecharge_Safecharge_Model_Api_Request_Payment_Payment3D
     */
    public function setCardCvv($cardCvv)
    {
        $this->cardCvv = $cardCvv;

        return $this;
    }

    /**
     * @param string $paResponse
     *
     * @return Safecharge_Safecharge_Model_Api_Request_Payment_Payment3D
     */
    public function setPaResponse($paResponse)
    {
        $this->paResponse = $paResponse;

        return $this;
    }
}
