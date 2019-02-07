<?php

/**
 * Safecharge Safecharge api payment refund request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Request_Payment_Refund
    extends Safecharge_Safecharge_Model_Api_Request_Payment_Abstract
{
    /**
     * @return string
     */
    protected function getRequestMethod()
    {
        return Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_REFUND;
    }

    /**
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_REFUND;
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

        /** @var int|null $transactionId */
        $transactionId = $orderPayment->getRefundTransactionId();
        if ($transactionId === null) {
            throw new Mage_Payment_Exception(
                __('Invoice transaction id has been not provided.')
            );
        }

        $transaction = Mage::getModel('sales/order_payment_transaction')
            ->getCollection()
            ->addAttributeToFilter('order_id', array('eq' => $order->getId()))
            ->addAttributeToFilter('txn_id', array('eq' => $transactionId))
            ->getFirstItem();

        $authCode = null;
        if (!$transaction->getId()) {
            $authCode = $orderPayment->getAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::TRANSACTION_AUTH_CODE_KEY
            );
        } else {
            $transactionDetails = $transaction->getAdditionalInformation(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS
            );

            if (empty($transactionDetails['authCode'])) {
                $authCode = $orderPayment->getAdditionalInformation(
                    Safecharge_Safecharge_Model_Safecharge::TRANSACTION_AUTH_CODE_KEY
                );
            } else {
                $authCode = $transactionDetails['authCode'];
            }
        }

        if ($authCode === null) {
            throw new Mage_Payment_Exception(
                __('Transaction does not contain authorization code.')
            );
        }

        $params = array(
            'clientUniqueId' => $order->getIncrementId(),
            'currency' => $order->getBaseCurrencyCode(),
            'amount' => (float)$this->amount,
            'relatedTransactionId' => $transactionId,
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
