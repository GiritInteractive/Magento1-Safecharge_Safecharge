<?php

/**
 * Safecharge Safecharge payment controller.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var Safecharge_Safecharge_Helper_Config
     */
    protected $moduleConfig;

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        $this->moduleConfig = Mage::helper('safecharge_safecharge/config');
    }

    /**
     * @return void
     */
    public function authenticateAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * @return void
     */
    public function externalAction()
    {
      $this->loadLayout();
      $this->renderLayout();
    }

    /**
     * @return void
     * @throws Varien_Exception
     * @throws Mage_Core_Exception
     */
    public function updateAction()
    {
        $params = $this->getRequest()->getParams();

        if ($this->moduleConfig->isDebugEnabled() === true) {
            Mage::log(
                'Redirect Update Response: ' . json_encode($params),
                null,
                'safecharge_safecharge_payment_update.log',
                true
            );
        }

        $orderId = $this->getRequest()->getParam('order');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();

        /** @var Safecharge_Safecharge_Model_Api_Request_Payment_Payment3D $apiRequest */
        $apiRequest = $this
            ->getPaymentRequestFactory()
            ->create(
                Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_PAYMENT_3D,
                $payment,
                $order->getBaseGrandTotal()
            );

        $userPaymentOptionId = $payment->getAdditionalInformation(
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_USER_PAYMENT_OPTION_ID
        );
        $cardCvv = $payment->getAdditionalInformation(
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_CARD_CVV
        );

        try {
            $apiRequest
                ->setUserPaymentOptionId($userPaymentOptionId)
                ->setCardCvv($cardCvv)
                ->setPaResponse(!empty($params['PaRes']) ? $params['PaRes'] : null)
                ->process();
        } catch (Mage_Payment_Exception $e) {
            Mage::getSingleton('checkout/session')->addError(
                __(
                    'Order has been placed but unfortunately payment has been not '
                    . 'authenticated properly.'
                )
            );
        }

        $userPaymentKey = Safecharge_Safecharge_Model_Safecharge::TRANSACTION_USER_PAYMENT_OPTION_ID;
        if ($payment->getAdditionalInformation($userPaymentKey)) {
            $payment->unsAdditionalInformation($userPaymentKey);
        }

        $payment->save();

        $this->_redirect('checkout/onepage/success');
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Request_Payment_Factory
     */
    protected function getPaymentRequestFactory()
    {
        return Mage::getSingleton('safecharge_safecharge/api_request_payment_factory');
    }
}
