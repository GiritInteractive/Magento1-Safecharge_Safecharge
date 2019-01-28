<?php
/**
 * Safecharge Safecharge api token request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Request_GetMerchantPaymentMethods
    extends Safecharge_Safecharge_Model_Api_Request_Abstract
{
      /**
      * @var Mage_Checkout_Model_Session
      */
    protected $checkoutSession = Mage::getSingleton('checkout/session');
    /**
     * @return string
     */
    protected function getRequestMethod()
    {
        return Safecharge_Safecharge_Model_Api_Request_Abstract::GET_MERCHANT_PAYMENT_METHODS_METHOD;
    }
    /**
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return Safecharge_Safecharge_Model_Api_Response_Abstract::GET_MERCHANT_PAYMENT_METHODS_HANDLER;
    }
    /**
     * @return array
     * @throws Exception
     * @throws Mage_Core_Exception
     * @throws Mage_Payment_Exception
     */
    protected function getParams()
    {
      $tokenRequest = $this->getRequestFactory()
          ->create(Safecharge_Safecharge_Model_Api_Request_Abstract::METHOD_SESSION_TOKEN);
      $tokenResponse = $tokenRequest->process();
      $checkoutSession->getQuote();
      $billing = ($quote) ? $quote->getBillingAddress() : null;
      $countryCode = ($billing) ? $billing->getCountryId() : null;
      $params = array(
        'sessionToken' => $tokenResponse->getToken(),
        "currencyCode" => $quote->getBaseCurrencyCode(),
        "countryCode" => $countryCode,
        "languageCode", "eng",
      )
      $params = array_merge_recursive($params, parent::getParams());
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
            'timeStamp',
        );
    }
}
