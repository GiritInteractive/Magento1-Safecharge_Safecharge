<?php

/**
 * Safecharge Safecharge api payment cc request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Request_Payment_Cc
    extends Safecharge_Safecharge_Model_Api_Request_Payment_Abstract
{
    /**
     * @return string
     */
    protected function getRequestMethod()
    {
        return Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_CC;
    }

    /**
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_CC;
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

        $this->processCardTokenization();

        $tokenRequest = $this->getRequestFactory()
            ->create(Safecharge_Safecharge_Model_Api_Request_Abstract::METHOD_SESSION_TOKEN);
        $tokenResponse = $tokenRequest->process();

        $params = array_merge_recursive(
            $this->getOrderData($order),
            $this->getPaymentData(),
            array(
                'sessionToken' => $tokenResponse->getToken(),
                'transactionType' => $this->getActionType(),
                'isRebilling' => 0,
                'amount' => (float)$this->amount,
            ),
            parent::getParams()
        );

        $this->requestEntity
            ->setParentRequestId($tokenRequest->getRequestId())
            ->setIncrementId($order->getIncrementId())
            ->save();

        return $params;
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
}
