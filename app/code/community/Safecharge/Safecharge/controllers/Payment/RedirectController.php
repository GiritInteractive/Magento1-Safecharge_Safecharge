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
     * @throws Varien_Exception
     */
    public function pendingAction()
    {
      $response = $this->getRequest()->getParams();

      if ($this->moduleConfig->isDebugEnabled() === true) {
          Mage::log(
              'Redirect Pending Response: '
              . var_export($response, true),
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


          if (isset($params['TransactionID']) && $params['TransactionID']) {
            $orderPayment->setAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::TRANSACTION_ID,
                $response['TransactionID']
            );
          }

          if (isset($params['AuthCode']) && $params['AuthCode']) {
            $orderPayment->setAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::TRANSACTION_AUTH_CODE_KEY,
                $response['AuthCode']
            );
          }

          if (isset($params['payment_method']) && $params['payment_method']) {
            $orderPayment->setAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::TRANSACTION_EXTERNAL_PAYMENT_METHOD,
                $response['payment_method']
            );
          }

          $orderPayment->setTransactionAdditionalInfo(
              Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
              $response
          );

          $history = Mage::getModel('sales/order_status_history')
            ->setOrder($order);

          $params['Status'] = (isset($params['Status'])) ? $params['Status'] : null;

          if (in_array(strtolower($params['Status']), ['declined', 'error'])) {
              $params['ErrCode'] = (isset($params['ErrCode'])) ? $params['ErrCode'] : "Unknown";
              $params['ExErrCode'] = (isset($params['ExErrCode'])) ? $params['ExErrCode'] : "Unknown";
              $history
                ->setComment("Payment returned a '{$params['Status']}' status (Code: {$params['ErrCode']}, Reason: {$params['ExErrCode']}).")
                ->setData('entity_name', Mage_Sales_Model_Order::HISTORY_ENTITY_NAME);
          } elseif ($params['Status']) {
            $history
              ->setComment("Payment returned a '" . $params['Status'] . "' status")
              ->setData('entity_name', Mage_Sales_Model_Order::HISTORY_ENTITY_NAME);
          }

          if (strtolower($params['Status']) === "pending") {
              $order->setState(Mage_Sales_Model_Order::STATE_NEW, true)->setStatus('pending');
          }

          Mage::getSingleton('checkout/session')
              ->setLastSuccessQuoteId($order->getQuoteId())
              ->setLastQuoteId($order->getQuoteId())
              ->setLastOrderId($order->getId());

          $history->save();
          $orderPayment->save();
          $order->save();


      } catch (Mage_Payment_Exception $e) {
        if ($this->moduleConfig->isDebugEnabled() === true) {
          Mage::log(
              'Pending Callback Process Error: '
              . var_export($response, true),
              null,
              'safecharge_safecharge_payment_redirect.log',
              true
          );
        }

        Mage::getSingleton('checkout/session')->addError($e->getMessage());
      }

      $this->_redirect('checkout/onepage/success/');
    }

    /**
     * @return void
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     * @throws Exception
     */
    public function successAction()
    {

        $response = $this->getRequest()->getParams();

        if ($this->moduleConfig->isDebugEnabled() === true) {
            Mage::log(
                'Redirect Success Response: '
                . var_export($response, true),
                null,
                'safecharge_safecharge_payment_redirect.log',
                true
            );
        }

        try {
            $result = $this->placeOrder();
            Mage::log($result,null,'safecharge_safecharge_payment_redirect.log',true);
            if ($result->getSuccess() !== true) {
                Mage::log($result->getErrorMessage(),null,'safecharge_safecharge_payment_redirect.log',true);
                throw new Mage_Payment_Exception(__($result->getErrorMessage()));
            }
            Mage::log(__LINE__,null,'safecharge_safecharge_payment_redirect.log',true);
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load($result->getOrderId());
            Mage::log(__LINE__,null,'safecharge_safecharge_payment_redirect.log',true);
            /** @var Mage_Sales_Model_Order_Payment $payment */
            $orderPayment = $order->getPayment();
Mage::log(__LINE__,null,'safecharge_safecharge_payment_redirect.log',true);
            if (!in_array(strtolower($params['Status']), ['approved', 'success'])) {
              Mage::log('Your payment failed.',null,'safecharge_safecharge_payment_redirect.log',true);
                throw new Mage_Payment_Exception(__('Your payment failed.'));
            }
Mage::log(__LINE__,null,'safecharge_safecharge_payment_redirect.log',true);
            if (isset($params['TransactionID']) && $params['TransactionID']) {
              $orderPayment->setAdditionalInformation(
                  Safecharge_Safecharge_Model_Safecharge::TRANSACTION_ID,
                  $response['TransactionID']
              );
            }
Mage::log(__LINE__,null,'safecharge_safecharge_payment_redirect.log',true);
            if (isset($params['AuthCode']) && $params['AuthCode']) {
              $orderPayment->setAdditionalInformation(
                  Safecharge_Safecharge_Model_Safecharge::TRANSACTION_AUTH_CODE_KEY,
                  $response['AuthCode']
              );
            }
Mage::log(__LINE__,null,'safecharge_safecharge_payment_redirect.log',true);
            if (isset($params['payment_method']) && $params['payment_method']) {
              $orderPayment->setAdditionalInformation(
                  Safecharge_Safecharge_Model_Safecharge::TRANSACTION_EXTERNAL_PAYMENT_METHOD,
                  $response['payment_method']
              );
            }
Mage::log(__LINE__,null,'safecharge_safecharge_payment_redirect.log',true);
            $orderPayment->setTransactionAdditionalInfo(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                $response
            );
Mage::log(__LINE__,null,'safecharge_safecharge_payment_redirect.log',true);
            Mage::getSingleton('checkout/session')
                ->setLastSuccessQuoteId($order->getQuoteId())
                ->setLastQuoteId($order->getQuoteId())
                ->setLastOrderId($order->getId());
Mage::log(__LINE__,null,'safecharge_safecharge_payment_redirect.log',true);
            $orderPayment->save();
Mage::log(__LINE__,null,'safecharge_safecharge_payment_redirect.log',true);
            $order->save();
Mage::log(__LINE__,null,'safecharge_safecharge_payment_redirect.log',true);
        } catch (Mage_Payment_Exception $e) {
          if ($this->moduleConfig->isDebugEnabled() === true) {
            Mage::log(
                'Success Callback Process Error: '
                . var_export($response, true),
                null,
                'safecharge_safecharge_payment_redirect.log',
                true
            );
          }

          Mage::getSingleton('checkout/session')->addError($e->getMessage());
          $this->_redirect('checkout/cart/');
        }

        $this->_redirect('checkout/onepage/success/');
    }

    /**
     * @return void
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     * @throws Exception
     */
     public function dmnAction()
     {
       if ($this->moduleConfig->isActive() === true) {
         try{
           $params = array_merge(
             $this->getRequest()->getParams(),
             $this->getRequest()->getPostValue()
           );

           if (Mage::helper('safecharge_safecharge/config')->isDebugEnabled() === true) {
             Mage::log('DMN Params: ' . json_encode($params),null,'safecharge_safecharge_payment_redirect.log',true);
           }

           $this->validateChecksum($params);

           if (isset($params["merchant_unique_id"]) && $params["merchant_unique_id"]) {
               $orderIncrementId = $params["merchant_unique_id"];
           } elseif (isset($params["order"]) && $params["order"]) {
               $orderIncrementId = $params["order"];
           } elseif (isset($params["orderId"]) && $params["orderId"]) {
               $orderIncrementId = $params["orderId"];
           } else {
               $orderIncrementId = null;
           }

           $tryouts = 0;
           do {
               $tryouts++;

               /** @var Order $order */
               $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
               if (!($order && $order->getId())) {
                   sleep(3);
               }
           } while ($tryouts <=10 && !($order && $order->getId()));

           if (!($order && $order->getId())) {
               throw new Mage_Payment_Exception(__('Order #%1 not found!', $orderIncrementId));
           }

           /** @var OrderPayment $payment */
           $orderPayment = $order->getPayment();

           $transactionId = $params['TransactionID'];
           $orderPayment->setAdditionalInformation(
               Safecharge_Safecharge_Model_Safecharge::TRANSACTION_ID,
               $transactionId
           );

           if (isset($params['AuthCode']) && $params['AuthCode']) {
               $orderPayment->setAdditionalInformation(
                   Safecharge_Safecharge_Model_Safecharge::TRANSACTION_AUTH_CODE_KEY,
                   $params['AuthCode']
               );
           }

           if (isset($params['payment_method']) && $params['AuthCode']) {
               $orderPayment->setAdditionalInformation(
                   Safecharge_Safecharge_Model_Safecharge::TRANSACTION_EXTERNAL_PAYMENT_METHOD,
                   $params['payment_method']
               );
           }

           $orderPayment->setTransactionAdditionalInfo(
               Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
               $params
           );

           $history = Mage::getModel('sales/order_status_history')
             ->setOrder($order);

           $params['Status'] = $params['Status'] ?: null;
           if (in_array(strtolower($params['Status']), ['declined', 'error'])) {
               $params['ErrCode'] = (isset($params['ErrCode'])) ? $params['ErrCode'] : "Unknown";
               $params['ExErrCode'] = (isset($params['ExErrCode'])) ? $params['ExErrCode'] : "Unknown";
               $history
                 ->setComment("Payment returned a '{$params['Status']}' status (Code: {$params['ErrCode']}, Reason: {$params['ExErrCode']}).")
                 ->setData('entity_name', Mage_Sales_Model_Order::HISTORY_ENTITY_NAME);
           } elseif ($params['Status']) {
               $history
                  ->setComment("Payment returned a '" . $params['Status'] . "' status")
                  ->setData('entity_name', Mage_Sales_Model_Order::HISTORY_ENTITY_NAME);
           }

           if (strtolower($params['Status']) === "pending") {
             $order->setState(Mage_Sales_Model_Order::STATE_NEW, true);
             $order->setStatus('pending');
           }

           if (in_array(strtolower($params['Status']), ['approved', 'success'])&& $orderPayment->getAdditionalInformation(Safecharge_Safecharge_Model_Safecharge::KEY_CHOSEN_APM_METHOD) !== Safecharge_Safecharge_Model_Safecharge::APM_METHOD_CC) {
               $params['transactionType'] = isset($params['transactionType']) ? $params['transactionType'] : null;
               $invoiceTransactionId = $transactionId;
               $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
               $isSettled = false;

               $formattedAmount = $order
                   ->getBaseCurrency()
                   ->formatTxt($order->getBaseGrandTotal());

               switch (strtolower($params['transactionType'])) {
                 case 'auth':
                     if ($this->moduleConfig->getPaymentAction() === Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE) {
                       $apiRequest = $this
                           ->getPaymentRequestFactory()
                           ->create(
                               Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_SETTLE,
                               $orderPayment,
                               $order->getBaseGrandTotal()
                           );

                           /** @var Safecharge_Safecharge_Model_Api_Response_Payment_Settle $settleResponse */
                           $settleResponse = $apiRequest->process();
                           $transactionId = $settleResponse->getTransactionId() ?: $transactionId;

                        $message = Mage::helper('sales')->__('Captured amount of %s online.', $formattedAmount );
                        $state = Mage_Sales_Model_Order::STATE_PROCESSING;
                        $status = Safecharge_Safecharge_Model_Safecharge::SC_SETTLED;
                        $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                        $isSettled = true;
                     } else {
                        $message = Mage::helper('sales')->__( 'Authorized amount of %s.', $formattedAmount);
                        $state = Mage_Sales_Model_Order::STATE_PROCESSING;
                        $status = Safecharge_Safecharge_Model_Safecharge::SC_AUTH;
                        $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
                     }
                     break;
                case 'sale':
                  if ($this->moduleConfig->getPaymentAction() === Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE) {
                      $message = Mage::helper('sales')->__('Captured amount of %s online.', $formattedAmount );
                      $state = Mage_Sales_Model_Order::STATE_PROCESSING;
                      $status = Safecharge_Safecharge_Model_Safecharge::SC_SETTLED;
                      $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                      $isSettled = true;
                  }
                  break;
               }

               $orderPayment
                   ->setTransactionId($response['TransactionID'])
                   ->setIsTransactionPending(false)
                   ->setIsTransactionClosed($isSettled ? 1 : 0);

               if ($transactionType === Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE) {
                       /** @var Mage_Sales_Model_Order_Invoice $invoice */
                       foreach ($order->getInvoiceCollection() as $invoice) {
                           $invoice
                               ->pay()
                               ->save();
                    }
                }

               $transaction = $orderPayment->addTransaction($transactionType);

               $message = $orderPayment->prependMessage($message);
               $order->setState($state, $status, $message);


               $orderPayment->save();
               $order->save();

               Mage::getSingleton('checkout/session')
                   ->setLastSuccessQuoteId($order->getQuoteId())
                   ->setLastQuoteId($order->getQuoteId())
                   ->setLastOrderId($order->getId());
           }

         } catch (\Exception $e) {
           if ($this->moduleConfig->isDebugEnabled() === true) {
               Mage::log(
                 'DMN Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                 null,
                 'safecharge_safecharge_payment_redirect.log',
                 true
               );
           }

           return $this->getResponse()
             ->setHttpResponseCode(500)
             ->setBody(Mage::helper('core')->jsonEncode(["error" => 1, "message" => $e->getMessage()]));
         }
       }

       if ($this->moduleConfig->isDebugEnabled() === true) {
           Mage::log(
             'DMN Success for order #' . $orderIncrementId,
             null,
             'safecharge_safecharge_payment_redirect.log',
             true
           );
       }

       return $this->getResponse()
         ->setHttpResponseCode(200)
         ->setBody(Mage::helper('core')->jsonEncode(["error" => 0, "message" => "SUCCESS"]));
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

    /**
     *
     *
     * @return Boolean
     */
    private function validateChecksum($params)
    {
        if (!isset($params["advanceResponseChecksum"])) {
          throw new Mage_Payment_Exception(
              __('Required key advanceResponseChecksum for checksum calculation is missing.')
          );
        }

        $concat = $this->moduleConfig->getMerchantSecretKey();
        foreach (['totalAmount', 'currency', 'responseTimeStamp', 'PPP_TransactionID', 'Status', 'productId'] as $checksumKey) {
            if (!isset($params[$checksumKey])) {
                throw new Mage_Payment_Exception(
                    __('Required key %1 for checksum calculation is missing.', $checksumKey)
                );
            }
            if (is_array($params[$checksumKey])) {
                foreach ($params[$checksumKey] as $subKey => $subVal) {
                    $concat .= $subVal;
                }
            } else {
                $concat .= $params[$checksumKey];
            }
        }
        $checksum = hash('sha256', utf8_encode($concat));
        if ($params["advanceResponseChecksum"] !== $checksum) {
            throw new Mage_Payment_Exception(
                __('Checksum validation failed!')
            );
        }
        return true;
    }

}
