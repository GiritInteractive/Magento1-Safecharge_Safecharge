<?php

/**
 * Safecharge Safecharge abstract api request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
abstract class Safecharge_Safecharge_Model_Api_Request_Abstract
{
    /**
     * Payment gateway endpoints.
     */
    const LIVE_ENDPOINT = 'https://secure.safecharge.com/ppp/';
    const TEST_ENDPOINT = 'https://ppp-test.safecharge.com/ppp/';

    const METHOD_SESSION_TOKEN = 'getSessionToken';
    const GET_MERCHANT_PAYMENT_METHODS_METHOD = 'getMerchantPaymentMethods';
    const PAYMENT_APM_METHOD = 'paymentApm';


    /**
     * @var Safecharge_Safecharge_Helper_Config
     */
    protected $config;

    /**
     * @var Safecharge_Safecharge_Model_Api_Curl
     */
    protected $curl;

    /**
     * @var Safecharge_Safecharge_Model_Request|null
     */
    protected $requestEntity;

    /**
     * Safecharge_Safecharge_Model_Api_Request_Abstract constructor.
     */
    public function __construct()
    {
        $this->config = Mage::helper('safecharge_safecharge/config');
        $this->curl = Mage::getModel('safecharge_safecharge/api_curl');
    }

    /**
     * @return string
     */
    protected function getEndpoint()
    {
        $endpoint = self::LIVE_ENDPOINT;
        if ($this->config->isTestModeEnabled() === true) {
            $endpoint = self::TEST_ENDPOINT;
        }

        $endpoint .= 'api/v1/';
        $method = $this->getRequestMethod();
        $endpoint = $endpoint . $method . '.do';

        return $endpoint;
    }

    /**
     * @return array
     */
    protected function getHeaders()
    {
        return array(
            'Content-Type: application/json',
        );
    }

    /**
     * @return array
     * @throws Mage_Payment_Exception
     * @throws Exception
     */
    protected function prepareParams()
    {
        $params = $this->getParams();

        $checksumKeys = $this->getChecksumKeys();
        if (empty($checksumKeys)) {
            return $params;
        }

        $concat = '';
        foreach ($checksumKeys as $checksumKey) {
            if (!isset($params[$checksumKey])) {
                throw new Mage_Payment_Exception(
                    __(
                        'Required key "%1" for checksum calculation is missing.',
                        $checksumKey
                    )
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

        $concat .= $this->config->getMerchantSecretKey();
        $concat = utf8_encode($concat);
        $params['checksum'] = hash('sha256', $concat);

        return $params;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getParams()
    {
        $this->initRequest();

        $params = array(
            'merchantId' => $this->config->getMerchantId(),
            'merchantSiteId' => $this->config->getMerchantSiteId(),
            'clientRequestId' => $this->getRequestId(),
            'timeStamp' => Mage::getSingleton('core/date')->gmtDate('YmdHis'),
            'merchantDetails' => [
              'customField1' => $this->config->getSourcePlatformField(),
            ],
            'encoding' => 'UTF-8'
        );

        return $params;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getRequestId()
    {
        if ($this->requestEntity === null) {
            $this->initRequest();
        }

        return $this->requestEntity->getRequestId();
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Request_Abstract
     * @throws Exception
     */
    protected function initRequest()
    {
        if ($this->requestEntity === null) {
            $requestEntity = Mage::getModel('safecharge_safecharge/request');
            $requestEntity
                ->setMethod($this->getRequestMethod())
                ->save();

            $this->requestEntity = $requestEntity;
        }

        return $this;
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

    /**
     * @return string
     */
    abstract protected function getRequestMethod();

    /**
     * @return string
     */
    abstract protected function getResponseHandlerType();

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Abstract
     * @throws Mage_Core_Exception
     * @throws Mage_Payment_Exception
     * @throws Exception
     */
    public function process()
    {
        $this->sendRequest();

        return $this
            ->getResponseHandler()
            ->process();
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Request_Abstract
     * @throws Mage_Payment_Exception
     * @throws Exception
     */
    protected function sendRequest()
    {
        $endpoint = $this->getEndpoint();
        $headers = $this->getHeaders();
        $params = $this->prepareParams();

        $filteredParams = $this->filterPrivateDataArray($params);

        if (Mage::helper('safecharge_safecharge/config')->isDebugEnabled() === true) {
            Mage::log(
                'Request: '
                . var_export(
                    array(
                        'Endpoint' => $endpoint,
                        'Type' => 'POST',
                        'Headers' => $headers,
                        'Body' => $filteredParams,
                    ),
                    true
                ),
                null,
                'safecharge_safecharge_payment.log',
                true
            );
        }

        $this->requestEntity
            ->setRequest(
                array(
                    'Endpoint' => $endpoint,
                    'Type' => 'POST',
                    'Headers' => $headers,
                    'Body' => $filteredParams,
                )
            )
            ->save();

        $this->curl->post($endpoint, $headers, $params);

        return $this;
    }

    /**
     * @return false|Safecharge_Safecharge_Model_Api_Response_Abstract
     * @throws Mage_Core_Exception
     */
    protected function getResponseHandler()
    {
        $responseHandler = $this
            ->getResponseFactory()
            ->create($this->getResponseHandlerType(), $this->requestEntity, $this->curl);

        return $responseHandler;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Factory
     */
    protected function getResponseFactory()
    {
        return Mage::getSingleton('safecharge_safecharge/api_response_factory');
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    protected function getOrderData(Mage_Sales_Model_Order $order)
    {
        $billing = $order->getBillingAddress();

        $orderData = array(
            'userTokenId' => $order->getCustomerId() ?: $order->getCustomerEmail(),
            'clientUniqueId' => $order->getIncrementId(),
            'currency' => $order->getOrderCurrencyCode(),
            'amountDetails' => array(
                'totalShipping' => (float)$order->getBaseShippingAmount(),
                'totalHandling' => (float)0,
                'totalDiscount' => (float)abs($order->getBaseDiscountAmount()),
                'totalTax' => (float)$order->getBaseTaxAmount(),
            ),
            'items' => array(),
            'deviceDetails' => array(
                'deviceType' => 'DESKTOP',
                'ipAddress' => $order->getRemoteIp(),
            ),
        );

        if ($billing !== null) {
            $address = is_array($billing->getStreet())
                ? implode(' ', (array)$billing->getStreet())
                : '';
            if (strlen($address) > 60) {
                $address = substr($address, 0, 60);
            }

            $orderData['billingAddress'] = array(
                'firstName' => $billing->getFirstname(),
                'lastName' => $billing->getLastname(),
                'address' => $address,
                'cell' => '',
                'phone' => $billing->getTelephone(),
                'zip' => $billing->getPostcode(),
                'city' => $billing->getCity(),
                'country' => $billing->getCountryId(),
                'state' => $billing->getRegionCode(),
                'email' => $billing->getEmail(),
            );

            $orderData = array_merge($orderData, $orderData['billingAddress']);
        }

        $orderItems = $order->getAllVisibleItems();
        foreach ($orderItems as $orderItem) {
            $price = (float)$orderItem->getBasePrice();
            if (!$price) {
                continue;
            }

            $orderData['items'][] = array(
                'name' => $orderItem->getName(),
                'price' => $price,
                'quantity' => (int)$orderItem->getQtyOrdered(),
            );
        }

        return $orderData;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Request_Factory
     */
    protected function getRequestFactory()
    {
        return Mage::getSingleton('safecharge_safecharge/api_request_factory');
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function filterPrivateDataArray(array $data)
    {
        $privateKeys = array(
            'cardNumber',
            'CVV',
        );

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->filterPrivateDataArray($value);
                continue;
            }

            if (in_array($key, $privateKeys, true)) {
                switch ($key) {
                    case 'cardNumber':
                        $data[$key] = 'xxxx-' . substr($value, -4);
                        break;
                    default:
                        $data[$key] = '***';
                }
            }
        }

        return $data;
    }

    /**
     * @param Quote $quote
     *
     * @return array
     */
    protected function getQuoteData($quote)
    {
        /** @var OrderAddressInterface $billing */
        $billing = $quote->getBillingAddress();

        $shipping = 0;
        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress !== null) {
            $shipping = $shippingAddress->getBaseShippingAmount();
        }

        $quoteData = [
            'userTokenId' => $quote->getCustomerId() ?: $quote->getCustomerEmail(),
            'clientUniqueId' => $quote->getIncrementId(),
            'currency' => $quote->getBaseCurrencyCode(),
            'amountDetails' => [
                'totalShipping' => (float)$shipping,
                'totalHandling' => (float)0,
                'totalDiscount' => (float)abs($quote->getBaseDiscountAmount()),
                'totalTax' => (float)$quote->getBaseTaxAmount(),
            ],
            'items' => [],
            'deviceDetails' => [
                'deviceType' => 'DESKTOP',
                'ipAddress' => $quote->getRemoteIp(),
            ],
            'ipAddress' => $quote->getRemoteIp(),
        ];

        if ($billing !== null) {
            $quoteData['billingAddress'] = [
                'firstName' => $billing->getFirstname(),
                'lastName' => $billing->getLastname(),
                'address' => is_array($billing->getStreet())
                    ? implode(' ', $billing->getStreet())
                    : '',
                'cell' => '',
                'phone' => $billing->getTelephone(),
                'zip' => $billing->getPostcode(),
                'city' => $billing->getCity(),
                'country' => $billing->getCountryId(),
                'state' => $billing->getRegionCode(),
                'email' => $billing->getEmail(),
            ];
            $quoteData = array_merge($quoteData, $quoteData['billingAddress']);
        }

        // Add items details.
        $quoteItems = $quote->getAllVisibleItems();
        foreach ($quoteItems as $quoteItem) {
            $price = (float)$quoteItem->getBasePrice();
            if (!$price) {
                continue;
            }

            $quoteData['items'][] = [
                'name' => $quoteItem->getName(),
                'price' => $price,
                'quantity' => (int)$quoteItem->getQty(),
            ];
        }

        return $quoteData;
    }
}
