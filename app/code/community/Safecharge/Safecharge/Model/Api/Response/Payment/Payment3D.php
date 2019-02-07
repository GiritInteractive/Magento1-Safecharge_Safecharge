<?php

/**
 * Safecharge Safecharge api payment payment 3d response model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Response_Payment_Payment3D
    extends Safecharge_Safecharge_Model_Api_Response_Payment_Abstract
{
    /**
     * @var int
     */
    protected $orderId;

    /**
     * @var int
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
        return self::METHOD_PAYMENT_3D;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_Payment3D
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
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_Payment3D
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     */
    protected function updateTransaction()
    {
        parent::updateTransaction();

        $isSettled = false;
        if ($this->config->getPaymentAction() === Safecharge_Safecharge_Model_Safecharge::ACTION_AUTHORIZE_CAPTURE) {
            $isSettled = true;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $this->orderPayment->getOrder();

        $formattedAmount = $order
            ->getBaseCurrency()
            ->formatTxt($order->getBaseGrandTotal());

        if ($isSettled) {
            $message = Mage::helper('sales')->__(
                'Captured amount of %s online.',
                $formattedAmount
            );
            $state = Mage_Sales_Model_Order::STATE_PROCESSING;
            $status = Safecharge_Safecharge_Model_Safecharge::SC_SETTLED;
            $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
        } else {
            $message = Mage::helper('sales')->__(
                'Authorized amount of %s.',
                $formattedAmount
            );
            $state = Mage_Sales_Model_Order::STATE_PROCESSING;
            $status = Safecharge_Safecharge_Model_Safecharge::SC_AUTH;
            $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
        }

        if ($this->orderPayment->getLastTransId()) {
            $this->orderPayment
                ->setParentTransactionId($this->orderPayment->getLastTransId());
        }

        $this->orderPayment
            ->setTransactionId($this->transactionId)
            ->setIsTransactionPending(false)
            ->setIsTransactionClosed($isSettled ? 1 : 0);

        $userPaymentKey = Safecharge_Safecharge_Model_Safecharge::TRANSACTION_USER_PAYMENT_OPTION_ID;
        if ($this->orderPayment->getAdditionalInformation($userPaymentKey)) {
            $this->orderPayment->unsAdditionalInformation($userPaymentKey);
        }

        $cvvKey = Safecharge_Safecharge_Model_Safecharge::TRANSACTION_CARD_CVV;
        if ($this->orderPayment->getAdditionalInformation($cvvKey)) {
            $this->orderPayment->unsAdditionalInformation($cvvKey);
        }

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

        if (Mage::getSingleton('checkout/session')->getAscUrl()) {
            if ($this->config->getPaymentAction() === Safecharge_Safecharge_Model_Safecharge::ACTION_AUTHORIZE_CAPTURE) {
                /** @var Mage_Sales_Model_Order_Invoice $invoice */
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $invoice
                        ->pay()
                        ->save();
                }
            }

            $this->orderPayment->addTransaction($transactionType);
            $order->setState($state, $status, $message);

            $this->orderPayment->save();
            $order->save();
        }

        return $this;
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
