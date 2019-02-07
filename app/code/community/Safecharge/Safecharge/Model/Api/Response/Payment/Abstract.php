<?php

/**
 * Safecharge Safecharge abstract api payment response model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
abstract class Safecharge_Safecharge_Model_Api_Response_Payment_Abstract
    extends Safecharge_Safecharge_Model_Api_Response_Abstract
{
    const METHOD_CC = 'payment_cc';
    const METHOD_SETTLE = 'payment_settle';
    const METHOD_REFUND = 'payment_refund';
    const METHOD_VOID = 'payment_void';
    const METHOD_CARD_TOKENIZATION = 'payment_card_tokenization';
    const METHOD_USER_PAYMENT_OPTION = 'payment_user_payment_option';
    const METHOD_DYNAMIC_3D = 'payment_dynamic3D';
    const METHOD_PAYMENT_3D = 'payment_payment3D';

    /**
     * @var Mage_Sales_Model_Order_Payment
     */
    protected $orderPayment;

    /**
     * @param Mage_Sales_Model_Order_Payment $orderPayment
     *
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_Abstract
     */
    public function setOrderPayment($orderPayment)
    {
        $this->orderPayment = $orderPayment;

        return $this;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_Abstract
     * @throws Mage_Core_Exception
     * @throws Mage_Payment_Exception
     * @throws Varien_Exception
     */
    public function process()
    {
        parent::process();

        $this
            ->processResponseData()
            ->updateTransaction();

        return $this;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_Abstract
     */
    abstract protected function processResponseData();

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_Abstract
     */
    protected function updateTransaction()
    {
        $body = $this->curl->getBody();
        $transactionKeys = $this->getRequiredResponseDataKeys();

        $transactionInformation = array();
        foreach ($transactionKeys as $transactionKey) {
            if (!isset($body[$transactionKey])) {
                continue;
            }

            $transactionInformation[$transactionKey] = $body[$transactionKey];
        }

        ksort($transactionInformation);

        $this->orderPayment->setTransactionAdditionalInfo(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $transactionInformation
        );

        return $this;
    }
}
