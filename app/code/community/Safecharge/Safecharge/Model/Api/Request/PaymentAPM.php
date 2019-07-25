<?php
/**
 * Safecharge Safecharge api token request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Request_PaymentAPM
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
      return Safecharge_Safecharge_Model_Api_Request_Abstract::PAYMENT_APM_METHOD;
  }

   /**
    * {@inheritdoc}
    *
    * @return string
    */
   protected function getResponseHandlerType()
   {
      return Safecharge_Safecharge_Model_Api_Response_Abstract::PAYMENT_APM_METHOD;
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

        $quote = Mage::getModel('checkout/cart')->getQuote();
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
        $urlBuilderHelper = Mage::helper('safecharge_safecharge/urlBuilder');

       $notificationUrl = $urlBuilderHelper->getApmDmnUrl($urlBuilderHelper->getReservedOrderId());
        $params = array_merge_recursive(
            $this->getQuoteData($quote),
            [
                'sessionToken' => $tokenResponse->getToken(),
                'amount' => $quote->getBaseGrandTotal(),
                'merchant_unique_id' => $urlBuilderHelper->getReservedOrderId(),
                'urlDetails' => [
                    'successUrl' => $urlBuilderHelper->getSuccessUrl(),
                    'failureUrl' => $urlBuilderHelper->getErrorUrl(),
                    'pendingUrl' => $urlBuilderHelper->getPendingUrl(),
                    'backUrl' => $urlBuilderHelper->getBackUrl(),
                    'notificationUrl' => $notificationUrl,
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
