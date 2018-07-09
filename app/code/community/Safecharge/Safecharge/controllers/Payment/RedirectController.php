<?php

/**
 * Safecharge Safecharge payment redirect controller.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Payment_RedirectController
    extends Mage_Core_Controller_Front_Action
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
     * @throws Varien_Exception
     */
    public function errorAction()
    {
        if ($this->moduleConfig->isDebugEnabled() === true) {
            Mage::log(
                'Redirect Error Response: '
                . var_export($this->getRequest()->getParams(), true),
                null,
                'safecharge_safecharge_payment_redirect.log',
                true
            );
        }

        Mage::getSingleton('checkout/session')->addError(
            __('Your payment failed.')
        );

        $this->_redirect('checkout/cart');
    }

    /**
     * @return void
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     * @throws Exception
     */
    public function successAction()
    {
        if ($this->moduleConfig->isDebugEnabled() === true) {
            Mage::log(
                'Redirect Success Response: '
                . var_export($this->getRequest()->getParams(), true),
                null,
                'safecharge_safecharge_payment_redirect.log',
                true
            );
        }

        try {
            $result = $this->placeOrder();

            if ($result->getSuccess() !== true) {
                throw new Mage_Payment_Exception(__($result->getErrorMessage()));
            }

            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load($result->getOrderId());

            /** @var Mage_Sales_Model_Order_Payment $payment */
            $orderPayment = $order->getPayment();

            $response = $this->getRequest()->getParams();

            if (strtolower($response['Status']) !== 'approved') {
                throw new Mage_Payment_Exception(__('Your payment failed.'));
            }

            $orderPayment->setAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::TRANSACTION_ID,
                $response['TransactionID']
            );
            $orderPayment->setAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::TRANSACTION_AUTH_CODE_KEY,
                $response['AuthCode']
            );
            $orderPayment->setAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::TRANSACTION_EXTERNAL_PAYMENT_METHOD,
                $response['payment_method']
            );
            $orderPayment->setTransactionAdditionalInfo(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                $response
            );

            $isSettled = false;
            if ($this->moduleConfig->getPaymentAction() === Safecharge_Safecharge_Model_Safecharge::ACTION_AUTHORIZE_CAPTURE) {
                $isSettled = true;

                $apiRequest = $this
                    ->getPaymentRequestFactory()
                    ->create(
                        Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_SETTLE,
                        $orderPayment,
                        $order->getBaseGrandTotal()
                    );
                /** @var Safecharge_Safecharge_Model_Api_Response_Payment_Settle $settleResponse */
                $settleResponse = $apiRequest->process();
            }

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

            $orderPayment
                ->setTransactionId($response['TransactionID'])
                ->setIsTransactionPending(false)
                ->setIsTransactionClosed($isSettled ? 1 : 0);

            if ($transactionType === Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE) {
                /** @var Mage_Sales_Model_Order_Invoice $invoice */
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $invoice
                        ->setTransactionId($settleResponse->getTransactionId())
                        ->pay()
                        ->save();
                }
            }

            $orderPayment->addTransaction($transactionType);
            $order->setState($state, $status, $message);

            $orderPayment->save();
            $order->save();

            Mage::getSingleton('checkout/session')
                ->setLastSuccessQuoteId($order->getQuoteId())
                ->setLastQuoteId($order->getQuoteId())
                ->setLastOrderId($order->getId());
        } catch (Mage_Payment_Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
        }

        $this->_redirect('checkout/onepage/success/');
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Request_Payment_Factory
     */
    protected function getPaymentRequestFactory()
    {
        return Mage::getSingleton('safecharge_safecharge/api_request_payment_factory');
    }

    /**
     * @return Varien_Object
     */
    protected function placeOrder()
    {
        $result = new Varien_Object();

        try {
            /** @var Mage_Sales_Model_Quote $quote */
            $quote = Mage::getModel('sales/quote')->load($this->getQuoteId());

            $quote->collectTotals();

            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();
            $quote->save();

            /** @var $order Mage_Sales_Model_Order */
            $order = $service->getOrder();

            $result
                ->setData('success', true)
                ->setData('order_id', $order->getId());

            Mage::dispatchEvent(
                'safecharge_place_order',
                array(
                    'result' => $result,
                    'action' => $this,
                )
            );
        } catch (\Exception $exception) {
            $result
                ->setData('error', true)
                ->setData(
                    'error_message',
                    __('An error occurred on the server. Please try to place the order again.')
                );
        }

        return $result;
    }

    /**
     * @return int
     * @throws Mage_Payment_Exception
     * @throws Varien_Exception
     */
    protected function getQuoteId()
    {
        $quoteId = (int)$this->getRequest()->getParam('order');

        if ((int)Mage::getSingleton('checkout/session')->getQuoteId() === $quoteId) {
            return $quoteId;
        }

        throw new Mage_Payment_Exception(
            __('Session has expired, order has been not placed.')
        );
    }

    /**
     * @return void
     */
    public function externalAction()
    {
        $url = Mage::helper('safecharge_safecharge/urlBuilder')->getUrl();

        if (Mage::helper('safecharge_safecharge/config')->isDebugEnabled() === true) {
            Mage::log(
                'Redirect URL: ' . $url,
                null,
                'safecharge_safecharge_payment_redirect.log',
                true
            );
        }

        $result['redirect'] = $url;

        $this->prepareDataJSON($result);
    }

    /**
     * @param array $response
     *
     * @return Zend_Controller_Response_Abstract
     */
    protected function prepareDataJSON($response)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }
}
