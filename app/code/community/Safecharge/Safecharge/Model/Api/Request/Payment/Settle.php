<?php

/**
 * Safecharge Safecharge api payment settle request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Request_Payment_Settle
    extends Safecharge_Safecharge_Model_Api_Request_Payment_Abstract
{
    /**
     * @return string
     */
    protected function getRequestMethod()
    {
        return Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_SETTLE;
    }

    /**
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_SETTLE;
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

        $tokenRequest = $this->getRequestFactory()
            ->create(Safecharge_Safecharge_Model_Api_Request_Abstract::METHOD_SESSION_TOKEN);
        $tokenResponse = $tokenRequest->process();

        $authCode = $orderPayment
            ->getAdditionalInformation(Safecharge_Safecharge_Model_Safecharge::TRANSACTION_AUTH_CODE_KEY);
        $relatedTransactionId = $orderPayment
            ->getAdditionalInformation(Safecharge_Safecharge_Model_Safecharge::TRANSACTION_ID);

        if (!$authCode) {
            throw new Mage_Payment_Exception(__('Authorization code is missing.'));
        }

        $params = array(
            'sessionToken' => $tokenResponse->getToken(),
            'clientUniqueId' => $order->getIncrementId(),
            'currency' => $order->getBaseCurrencyCode(),
            'amount' => (float)$this->amount,
            'relatedTransactionId' => $relatedTransactionId,
            'authCode' => $authCode,
            'descriptorMerchantName' => 'Merchant Name', // TODO: Consider adding to configuration.
            'descriptorMerchantPhone' => '12345789', // TODO: Consider adding to configuration.
            'comment' => 'No Comment',
            'urlDetails' => array(
                'notificationUrl' => '',
            ),
        );

        $params = array_merge_recursive($params, parent::getParams());

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
            'clientUniqueId',
            'amount',
            'currency',
            'relatedTransactionId',
            'authCode',
            'descriptorMerchantName',
            'descriptorMerchantPhone',
            'comment',
            'urlDetails',
            'timeStamp',
        );
    }
}
