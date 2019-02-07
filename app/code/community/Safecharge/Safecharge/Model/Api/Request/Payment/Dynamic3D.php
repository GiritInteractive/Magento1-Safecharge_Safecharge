<?php

/**
 * Safecharge Safecharge api payment dynamic 3d request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Request_Payment_Dynamic3D
    extends Safecharge_Safecharge_Model_Api_Request_Payment_Abstract
{
    /**
     * @return string
     */
    protected function getRequestMethod()
    {
        return Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_DYNAMIC_3D;
    }

    /**
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_DYNAMIC_3D;
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

        $tokenKey = Safecharge_Safecharge_Model_Safecharge::TRANSACTION_SESSION_TOKEN;
        if ($orderPayment->getAdditionalInformation($tokenKey)) {
            $orderPayment->unsAdditionalInformation($tokenKey);
        }

        $orderPayment->setAdditionalInformation(
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_SESSION_TOKEN,
            $tokenResponse->getToken()
        );

        $params = array_merge_recursive(
            $this->getOrderData($order),
            $this->getPaymentData(),
            array(
                'sessionToken' => $tokenResponse->getToken(),
                'isDynamic3D' => 1,
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
