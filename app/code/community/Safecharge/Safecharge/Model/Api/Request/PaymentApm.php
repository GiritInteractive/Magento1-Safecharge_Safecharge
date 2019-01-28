<?php

/**
 * Safecharge Safecharge api token request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Request_PaymentApm
    extends Safecharge_Safecharge_Model_Api_Request_Abstract
{

    /**
     * @var string|null
     */
    protected $paymentMethod;

    /**
    * @var Mage_Checkout_Model_Session
    */
    protected $checkoutSession = Mage::getSingleton('checkout/session');

    /**
     * @return $this
     */
    public function setPaymentMethod($paymentMethod)
    {
      $this->paymentMethod = trim((string)$paymentMethod);
      return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
      return $this->paymentMethod;
    }

    /**
     * @return string
     */
    protected function getRequestMethod()
    {
        return Safecharge_Safecharge_Model_Api_Request_Abstract::PAYMENT_APM_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return Safecharge_Safecharge_Model_Api_Response_Abstract::PAYMENT_APM_HANDLER;
    }

    /**
     * @return array
     * @throws Exception
     * @throws Mage_Core_Exception
     * @throws Mage_Payment_Exception
     */
    protected function getParams()
    {

      $quote = $checkoutSession->getQuote();
      $quotePayment = $quote->getPayment();

      $tokenRequest = $this->getRequestFactory()
          ->create(Safecharge_Safecharge_Model_Api_Request_Abstract::METHOD_SESSION_TOKEN);
      $tokenResponse = $tokenRequest->process();

      $tokenKey = Safecharge_Safecharge_Model_Safecharge::TRANSACTION_SESSION_TOKEN;
      $quotePayment->unsAdditionalInformation($tokenKey);
      $quotePayment->setAdditionalInformation(
          Safecharge_Safecharge_Model_Safecharge::TRANSACTION_SESSION_TOKEN,
          $tokenResponse->getToken()
      );

      $reservedOrderId = $quotePayment->getAdditionalInformation(
          Safecharge_Safecharge_Model_Safecharge::TRANSACTION_ORDER_ID
      );

      $urlBuilderHelper = Mage::helper('safecharge_safecharge/urlBuilder');
      $params = array_merge_recursive(
          $this->getQuoteData($quote),
          [
              'orderId' => $reservedOrderId,
              'sessionToken' => $tokenResponse->getToken(),
              'amount' => (float)$quote->getGrandTotal(),
              'merchant_unique_id' => $reservedOrderId,
              'urlDetails' => [
                  'successUrl' => $urlBuilderHelper->getApmSuccessUrl(),
                  'failureUrl' => $urlBuilderHelper->getApmErrorUrl(),
                  'pendingUrl' => $urlBuilderHelper->getApmPendingUrl(),
                  'backUrl' => $urlBuilderHelper->getBackUrl(),
                  'notificationUrl' => $urlBuilderHelper->getApmDmnUrl($reservedOrderId),
              ],
              'paymentMethod' => $this->getPaymentMethod(),
          ]
      );

      $params = array_merge_recursive($params, parent::getParams());


      /* TODO  */
      /*$this->safechargeLogger->updateRequest(
          $this->getRequestId(),
          [
              'parent_request_id' => $quotePayment->getAdditionalInformation(Payment::TRANSACTION_REQUEST_ID),
              'increment_id' => $this->config->getReservedOrderId(),
          ]
      );*/

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
