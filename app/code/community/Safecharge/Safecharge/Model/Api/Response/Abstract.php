<?php

/**
 * Safecharge Safecharge abstract api response model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
abstract class Safecharge_Safecharge_Model_Api_Response_Abstract
{
    const METHOD_SESSION_TOKEN = 'token';
    const GET_MERCHANT_PAYMENT_METHODS_METHOD = 'getMerchantPaymentMethods';
    const PAYMENT_APM_METHOD = 'paymentApm';

    /**
     * Response result const.
     */
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;

    /**
     * @var Safecharge_Safecharge_Model_Request
     */
    protected $requestEntity;

    /**
     * @var Safecharge_Safecharge_Model_Api_Curl
     */
    protected $curl;

    /**
     * @var Safecharge_Safecharge_Helper_Config
     */
    protected $config;

    /**
     * Safecharge_Safecharge_Model_Api_Request_Abstract constructor.
     */
    public function __construct()
    {
        $this->config = Mage::helper('safecharge_safecharge/config');
    }

    /**
     * @param Safecharge_Safecharge_Model_Request $requestEntity
     *
     * @return Safecharge_Safecharge_Model_Api_Response_Abstract
     */
    public function setRequestEntity(Safecharge_Safecharge_Model_Request $requestEntity)
    {
        $this->requestEntity = $requestEntity;

        return $this;
    }

    /**
     * @param Safecharge_Safecharge_Model_Api_Curl $curl
     *
     * @return Safecharge_Safecharge_Model_Api_Response_Abstract
     */
    public function setCurl(Safecharge_Safecharge_Model_Api_Curl $curl)
    {
        $this->curl = $curl;

        return $this;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Abstract
     * @throws Mage_Payment_Exception
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     */
    public function process()
    {
        $requestStatus = $this->getRequestStatus();

        if (Mage::helper('safecharge_safecharge/config')->isDebugEnabled() === true) {
            Mage::log(
                'Response: '
                . var_export($this->prepareResponseData(), true),
                null,
                'safecharge_safecharge_payment.log',
                true
            );
        }

        $this->requestEntity
            ->setResponse($this->prepareResponseData())
            ->setStatus(($requestStatus === true ? self::STATUS_SUCCESS : self::STATUS_FAILED))
            ->save();

        $this->persistRequestEntity();

        if ($requestStatus === false) {
            throw new Mage_Payment_Exception($this->getErrorMessage());
        }

        $this->validateResponseData();

        return $this;
    }

    /**
     * @return string
     */
    abstract protected function getResponseMethod();

    /**
     * @return string
     */
    protected function getErrorMessage()
    {
        $errorReason = $this->getErrorReason();
        if ($errorReason !== false) {
            return __('Request to payment gateway failed. Details: "%1".', $errorReason);
        }

        return __('Request to payment gateway failed.');
    }

    /**
     * @return bool
     */
    protected function getErrorReason()
    {
        return false;
    }

    /**
     * @return bool
     */
    protected function getRequestStatus()
    {
        $httpStatus = $this->curl->getStatus();
        if ($httpStatus !== 200 && $httpStatus !== 100) {
            return false;
        }

        $body = $this->curl->getBody();

        $responseStatus = strtolower(!empty($body['status']) ? $body['status'] : '');
        $responseTransactionStatus = strtolower(!empty($body['transactionStatus']) ? $body['transactionStatus'] : '');
        $responseTransactionType = strtolower(!empty($body['transactionType']) ? $body['transactionType'] : '');
        $responsetThreeDFlow = (int)(!empty($body['threeDFlow']) ? $body['threeDFlow'] : '');
        if (
            !(
                (!(in_array($responseTransactionType, ['auth', 'sale']) || ($responseTransactionType === 'sale3d' && $responsetThreeDFlow === 0)) && $responseStatus === 'success' && $responseTransactionType !== 'error') ||
                ($responseTransactionType === 'sale3d' && $responsetThreeDFlow === 0 && $responseTransactionStatus === 'approved') ||
                (in_array($responseTransactionType, ['auth', 'sale']) && $responseTransactionStatus === 'approved')
            )
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    protected function prepareResponseData()
    {
        return array(
            'Status' => $this->curl->getStatus(),
            'Headers' => $this->curl->getHeaders(),
            'Body' => $this->curl->getBody(),
        );
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Abstract
     * @throws Mage_Payment_Exception
     */
    protected function validateResponseData()
    {
        $requiredKeys = $this->getRequiredResponseDataKeys();
        $bodyKeys = array_keys((array)$this->curl->getBody());

        $diff = array_diff($requiredKeys, $bodyKeys);
        if (!empty($diff)) {
            throw new Mage_Payment_Exception(
                __(
                    'Required response data fields are missing: %1.',
                    implode(', ', $diff)
                )
            );
        }

        return $this;
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return array(
            'status',
        );
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Abstract
     * @throws Mage_Core_Exception
     */
    protected function persistRequestEntity()
    {
        $key = Safecharge_Safecharge_Model_Safecharge::REQUEST_ENTITY_PERSISTENCE;

        $persistence = array();
        if (Mage::registry($key)) {
            $persistence = Mage::registry($key);
            Mage::unregister($key);
        }

        $persistence[$this->getResponseMethod()] = $this->requestEntity;
        Mage::register($key, $persistence);

        return $this;
    }
}
