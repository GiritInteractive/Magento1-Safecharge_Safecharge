<?php

/**
 * Safecharge Safecharge api payment cancel request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Request_Payment_Cancel
    extends Safecharge_Safecharge_Model_Api_Request_Payment_Abstract
{
    /**
     * @return string
     */
    protected function getRequestMethod()
    {
        return Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_VOID;
    }

    /**
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_VOID;
    }

    /**
     * @return array
     * @throws Exception
     * @throws Mage_Payment_Exception
     */
    protected function getParams()
    {
        /** @var Mage_Sales_Model_Order_Payment $orderPayment */
        $orderPayment = $this->orderPayment;

        /** @var Mage_Sales_Model_Order $order */
        $order = $orderPayment->getOrder();

        $transaction = $orderPayment->getAuthorizationTransaction();
        $transactionDetails = $transaction->getAdditionalInformation(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS
        );

        $authCode = null;
        if (empty($transactionDetails['authCode'])) {
            $authCode = $orderPayment->getAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::TRANSACTION_AUTH_CODE_KEY
            );
        } else {
            $authCode = $transactionDetails['authCode'];
        }

        if ($authCode === null) {
            throw new Mage_Payment_Exception(
                __('Transaction does not contain authorization code.')
            );
        }

        $params = array(
            'clientUniqueId' => $order->getIncrementId(),
            'currency' => $order->getBaseCurrencyCode(),
            'amount' => (float)$order->getBaseGrandTotal(),
            'relatedTransactionId' => $transaction->getTxnId(),
            'authCode' => $authCode,
            'comment' => '',
            'urlDetails' => array(
                'notificationUrl' => '',
            ),
        );

        $params = array_merge_recursive($params, parent::getParams());

        $this->requestEntity
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
            'comment',
            'urlDetails',
            'timeStamp',
        );
    }
}
