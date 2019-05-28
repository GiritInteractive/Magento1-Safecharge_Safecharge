<?php

/**
 * Safecharge Safecharge config provider model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Helper_UrlBuilder
{
    /**
     * @var Safecharge_Safecharge_Helper_Config
     */
    protected $moduleConfig;

    /**
     * @var Mage_Checkout_Model_Session
     */
    protected $checkoutSession;

    /**
     * @var Mage_Core_Model_Url
     */
    protected $urlBuilder;

    /**
     * Safecharge_Safecharge_Helper_UrlBuilder constructor.
     */
    public function __construct() {
        $this->moduleConfig = Mage::helper('safecharge_safecharge/config');
        $this->checkoutSession = Mage::getSingleton('checkout/session');
        $this->urlBuilder = Mage::getSingleton('core/url');
    }

    /**
     * @return array
     */
    public function getQueryParams(){
      if ($this->moduleConfig->getPaymentSolution() === Safecharge_Safecharge_Model_Safecharge::PAYMENT_SOLUTION_INTEGRATED) {
          return '';
      }

      /** @var Mage_Sales_Model_Quote $quote */
      $quote = $this->checkoutSession->getQuote();
      $quotePayment = $quote->getPayment();

      $reservedOrderId = $quotePayment
        ->getAdditionalInformation(Safecharge_Safecharge_Model_Safecharge::TRANSACTION_ORDER_ID) ?: $this->getReservedOrderId();

      $queryParams = array(
            'merchant_id' => $this->moduleConfig->getMerchantId(),
            'merchant_site_id' => $this->moduleConfig->getMerchantSiteId(),
            'customField1' => $this->moduleConfig->getSourcePlatformField(),
            'total_amount' => round($quote->getBaseGrandTotal(), 2),
            'discount' => 0,
            'shipping' => 0,
            'total_tax' => 0,
            'currency' => $quote->getBaseCurrencyCode(),
            'user_token_id' => $quote->getCustomerId(),
            'time_stamp' => date('YmdHis'),
            'version' => '4.0.0',
            'success_url' => $this->getSuccessUrl(),
            'pending_url' => $this->getPendingUrl(),
            'error_url' => $this->getErrorUrl(), // TODO: Not sure if final solution.
            'back_url' => $this->getBackUrl(), // TODO: Not sure if final solution.
            'notify_url' => $this->getApmDmnUrl($reservedOrderId),
            'merchant_unique_id' => $reservedOrderId,
            'ipAddress' => $quote->getRemoteIp(),
            'encoding' => 'UTF-8',
     );

     if (($billing = $quote->getBillingAddress()) && $billing !== null) {
         $billingAddress = [
             'first_name' => $billing->getFirstname(),
             'last_name' => $billing->getLastname(),
             'address' => is_array($billing->getStreet()) ? implode(' ', $billing->getStreet()) : '',
             'cell' => '',
             'phone' => $billing->getTelephone(),
             'zip' => $billing->getPostcode(),
             'city' => $billing->getCity(),
             'country' => $billing->getCountryId(),
             'state' => $billing->getRegionCode(),
             'email' => $billing->getEmail(),
         ];
         $queryParams = array_merge($queryParams, $billingAddress);
     }

     $queryParams['item_name_1'] = 'product1';
     $queryParams['item_amount_1'] = $queryParams['total_amount'];
     $queryParams['item_quantity_1'] = 1;
     $queryParams['numberofitems'] = 1;

     $queryParams['checksum'] = hash('sha256', utf8_encode($this->moduleConfig->getMerchantSecretKey() . implode("", $queryParams)));

     return $queryParams;
    }

    /**
     * Return full endpoint;
     *
     * @return string
     */
    public function getEndpoint()
    {
        $endpoint = Safecharge_Safecharge_Model_Api_Request_Abstract::LIVE_ENDPOINT;
        if ($this->moduleConfig->isTestModeEnabled() === true) {
            $endpoint = Safecharge_Safecharge_Model_Api_Request_Abstract::TEST_ENDPOINT;
        }

        return $endpoint . 'purchase.do';
    }

    /**
     * @return string
     */
    public function getApmDmnUrl($incrementId = null, $storeId = null)
    {
        $quoteId = $this->checkoutSession->getQuoteId();
        $apmDmnUrl = $this->urlBuilder->getUrl(
          'safecharge/payment_redirect/dmn/order/' . ((is_null($incrementId)) ? $this->getReservedOrderId() : $incrementId));
        return $apmDmnUrl;
    }

    /**
     * @return string
     */
    public function getSuccessUrl()
    {
        $quoteId = $this->checkoutSession->getQuoteId();

        return $this->urlBuilder->getUrl(
            'safecharge/payment_redirect/success',
            array('order' => $quoteId)
        );
    }

    /**
     * @return string
     */
    public function getErrorUrl()
    {
        $quoteId = $this->checkoutSession->getQuoteId();

        return $this->urlBuilder->getUrl(
            'safecharge/payment_redirect/error',
            array('order' => $quoteId)
        );
    }

    /**
     * @return string
     */
    public function getPendingUrl()
    {
        $quoteId = $this->checkoutSession->getQuoteId();

        return $this->urlBuilder->getUrl(
            'safecharge/payment_redirect/pending',
            array('order' => $quoteId)
        );
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->urlBuilder->getUrl('checkout/cart');
    }

    public function getReservedOrderId()
    {
      $this->checkoutSession->getQuote()->reserveOrderId()->save();
      $reservedOrderId = $this->checkoutSession->getQuote()->getReservedOrderId();
      if (!$reservedOrderId) {
        $this->checkoutSession->getQuote()->reserveOrderId()->save();
        $reservedOrderId = $this->checkoutSession->getQuote()->getReservedOrderId();
      }
      return $reservedOrderId;
    }
}
