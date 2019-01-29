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
   * @var RequestFactory
   */
  protected $requestFactory;


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
   * {@inheritdoc}
   *
   * @return string
   */
  protected function getRequestMethod()
  {
      return self::PAYMENT_APM_METHOD;
  }

   /**
    * {@inheritdoc}
    *
    * @return string
    */
   protected function getResponseHandlerType()
   {
      return Safecharge_Safecharge_Model_Api_Response_Factory::PAYMENT_APM_METHOD;
   }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getParams()
    {

        /** @var Quote $quote */
        $checkoutSession = Mage::getSingleton('checkout/session');
        $quote = $checkoutSession->getQuote();

        $quotePayment = $quote->getPayment();

        $tokenRequest = $this->getRequestFactory()
            ->create(Safecharge_Safecharge_Model_Api_Request_Abstract::METHOD_SESSION_TOKEN);
        $tokenResponse = $tokenRequest->process();

        $tokenKey = Safecharge_Safecharge_Model_Safecharge::TRANSACTION_SESSION_TOKEN;
        if ($quotePayment->getAdditionalInformation($tokenKey)) {
            $quotePayment->unsAdditionalInformation($tokenKey);
        }

        $quotePayment->setAdditionalInformation(
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_SESSION_TOKEN,
            $tokenResponse->getToken()
        );

        $reservedOrderId = $quotePayment->getAdditionalInformation(Safecharge_Safecharge_Model_Safecharge::TRANSACTION_ORDER_ID);

        $params = array_merge_recursive(
            $this->getQuoteData($quote),
            [
                'orderId' => $reservedOrderId,
                'sessionToken' => $tokenResponse->getToken(),
                'amount' => (float)$quote->getGrandTotal(),
                'merchant_unique_id' => $reservedOrderId,
                'urlDetails' => [
                    'successUrl' => Mage::helper('safecharge_safecharge/urlBuilder')->getApmSuccessUrl(),
                    'failureUrl' => Mage::helper('safecharge_safecharge/urlBuilder')->getApmErrorUrl(),
                    'pendingUrl' => Mage::helper('safecharge_safecharge/urlBuilder')->getApmPendingUrl(),
                    'backUrl' => Mage::helper('safecharge_safecharge/urlBuilder')->getBackUrl(),
                    'notificationUrl' => $this->getPaymentMethod(),
                ],
                'paymentMethod' => $this->getPaymentMethod(),
            ]
        );

        $params = array_merge_recursive($params, parent::getParams());


        return $params;
    }

    /**
     * {@inheritdoc}
     *
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
            'timeStamp'
        );
    }

}
