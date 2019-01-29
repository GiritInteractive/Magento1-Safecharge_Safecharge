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
      * Store id.
      *
      * @var int
      */
     private $storeId;

    /**
     * Safecharge_Safecharge_Helper_UrlBuilder constructor.
     */
    public function __construct() {
        $this->moduleConfig = Mage::helper('safecharge_safecharge/config');
        $this->checkoutSession = Mage::getSingleton('checkout/session');
        $this->urlBuilder = Mage::getSingleton('core/url');
        $this->storeId = Mage::app()->getStore()->getStoreId();
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        if ($this->moduleConfig->getPaymentSolution() === Safecharge_Safecharge_Model_Safecharge::PAYMENT_SOLUTION_INTEGRATED) {
            return '';
        }

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $this->checkoutSession->getQuote();

        $shipping = 0;
        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress !== null) {
            $shipping = (float)$shippingAddress->getBaseShippingAmount();
        }

        $url = $this->getEndpoint();

        $totalTax = (float)$quote->getShippingAddress()->getBaseTaxAmount();

        $queryParams = array(
            'merchant_id' => $this->moduleConfig->getMerchantId(),
            'merchant_site_id' => $this->moduleConfig->getMerchantSiteId(),
            'total_amount' => round($quote->getBaseGrandTotal(), 2),
            'handling' => $totalTax,
            'discount' => round($quote->getBaseSubtotal() - $quote->getBaseSubtotalWithDiscount(), 2),
            'shipping' => round($shipping, 2),
            'currency' => $quote->getBaseCurrencyCode(),
            'user_token_id' => $quote->getCustomerId(),
            'time_stamp' => date('YmdHis'),
            'version' => '3.0.0',
            //'success_url' => $this->getSuccessUrl(), // TODO: Not sure if final solution.
            //'error_url' => $this->getErrorUrl(), // TODO: Not sure if final solution.
            //'back_url' => $this->getBackUrl(), // TODO: Not sure if final solution.
        );

        $concat = $this->moduleConfig->getMerchantSecretKey()
            . $queryParams['merchant_id']
            . $queryParams['currency']
            . $queryParams['total_amount'];

        $numberOfItems = 0;
        $i = 1;

        $quoteItems = $quote->getAllVisibleItems();
        foreach ($quoteItems as $quoteItem) {
            $price = $quoteItem->getBasePrice();
            if (!$price) {
                continue;
            }

            $queryParams['item_name_' . $i] = $quoteItem->getName();
            $queryParams['item_amount_' . $i] = round($price, 2);
            $queryParams['item_quantity_' . $i] = (int)$quoteItem->getQty();

            $numberOfItems++;

            $concat .= $queryParams['item_name_' . $i]
                . $queryParams['item_amount_' . $i]
                . $queryParams['item_quantity_' . $i];

            $i++;
        }

        $queryParams['numberofitems'] = $numberOfItems;

        $concat .= $queryParams['user_token_id']
            . $queryParams['time_stamp'];

        $concat = utf8_encode($concat);
        $queryParams['checksum'] = hash('sha256', $concat);

        $url .= '?' . http_build_query($queryParams);

        $url .= '&success_url=' . $this->getSuccessUrl(); // TODO: Not sure if final solution.
        $url .= '&error_url=' . $this->getErrorUrl(); // TODO: Not sure if final solution.
        $url .= '&back_url=' . $this->getBackUrl(); // TODO: Not sure if final solution.

        return $url;
    }

    /**
     * Return full endpoint;
     *
     * @return string
     */
    protected function getEndpoint()
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
    public function getBackUrl()
    {
        return $this->urlBuilder->getUrl('checkout/cart');
    }


    /**
     * @return string
     */
    public function getApmSuccessUrl()
    {
        $quoteId = $this->checkoutSession->getQuoteId();
        return $this->urlBuilder->getUrl(
            'safecharge/payment_apm/success',
            array('order' => $quoteId)
        );
    }
    /**
     * @return string
     */
    public function getApmErrorUrl()
    {
        $quoteId = $this->checkoutSession->getQuoteId();
        return $this->urlBuilder->getUrl(
            'safecharge/payment_apm/error',
            array('order' => $quoteId)
        );
    }
    /**
     * @return string
     */
    public function getApmPendingUrl()
    {
        $quoteId = $this->checkoutSession->getQuoteId();
        return $this->urlBuilder->getUrl(
            'safecharge/payment_apm/panding',
            array('order' => $quoteId)
        );
    }
    /**
     * @return string
     */
    public function getApmDmnUrl($incrementId = null, $storeId = null)
    {
        $quoteId = $this->checkoutSession->getQuoteId();
        $apmDmnUrl = Mage::app()
          ->getStore((is_null($incrementId)) ? $this->storeId : $storeId)
          ->getBaseUrl($type). 'safecharge/payment_apm/dmn/order/' . ((is_null($incrementId)) ? $this->getReservedOrderId() : $incrementId);
        return $apmDmnUrl;
    }

    public function getReservedOrderId()
    {
        $reservedOrderId = $this->checkoutSession->getQuote()->getReservedOrderId();
        if (!$reservedOrderId) {
            $this->checkoutSession->getQuote()->reserveOrderId()->save();
            $reservedOrderId = $this->checkoutSession->getQuote()->getReservedOrderId();
        }
        return $reservedOrderId;
    }

}
