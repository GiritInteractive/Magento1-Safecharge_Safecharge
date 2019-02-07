<?php

/**
 * Safecharge Safecharge observer model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     *
     * @return Safecharge_Safecharge_Model_Observer
     * @throws Exception
     */
    public function requestEntityPersistence(Varien_Event_Observer $observer)
    {
        $key = Safecharge_Safecharge_Model_Safecharge::REQUEST_ENTITY_PERSISTENCE;

        $persistence = Mage::registry($key);
        if (is_array($persistence)) {
            Mage::unregister($key);

            foreach ($persistence as $requestEntity) {
                $requestEntity
                    ->setId(null)
                    ->save();
            }
        }

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return Safecharge_Safecharge_Model_Observer|void
     */
    public function externalSolution(Varien_Event_Observer $observer)
    {
        /** @var Safecharge_Safecharge_Helper_Config $config */
        $config = Mage::helper('safecharge_safecharge/config');

        if ($config->getPaymentSolution() === Safecharge_Safecharge_Model_Safecharge::PAYMENT_SOLUTION_INTEGRATED) {
            return $this;
        }

        $redirectUrl = Mage::getUrl(
            'safecharge/payment_redirect/external',
            array('_secure' => true)
        );

        Mage::app()->getFrontController()->getResponse()->setRedirect($redirectUrl);
        Mage::app()->getResponse()->sendResponse();
        exit;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return Safecharge_Safecharge_Model_Observer
     * @throws Varien_Exception
     */
    public function invoicePay(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        $invoice = $observer->getEvent()->getInvoice();

        /** @var Mage_Sales_Model_Order $order */
        $order = $invoice->getOrder();

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();

        if ($payment->getMethod() !== Safecharge_Safecharge_Model_Safecharge::METHOD_CODE) {
            return $this;
        }

        if ($invoice->getState() !== Mage_Sales_Model_Order_Invoice::STATE_PAID) {
            return $this;
        }

        $status = Safecharge_Safecharge_Model_Safecharge::SC_SETTLED;

        $totalDue = $order->getBaseTotalDue();
        if ((float)$totalDue > 0.0) {
            $status = Safecharge_Safecharge_Model_Safecharge::SC_PARTIALLY_SETTLED;
        }

        $order->setStatus($status);

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return Safecharge_Safecharge_Model_Observer
     * @throws Varien_Exception
     */
    public function voidRegister(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $observer->getEvent()->getPayment();

        if ($payment->getMethod() !== Safecharge_Safecharge_Model_Safecharge::METHOD_CODE) {
            return $this;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();

        $order->setStatus(Safecharge_Safecharge_Model_Safecharge::SC_VOIDED);

        return $this;
    }
}
