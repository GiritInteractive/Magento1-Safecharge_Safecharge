<?php

/**
 * Safecharge Safecharge config helper.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Helper_Config
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * Return config path.
     *
     * @return string
     */
    protected function getConfigPath()
    {
        return sprintf('payment/%s/', Safecharge_Safecharge_Model_Safecharge::METHOD_CODE);
    }

    /**
     * @param string $fieldKey Field key.
     *
     * @return mixed
     */
    protected function getConfigValue($fieldKey)
    {
        if (isset($this->config[$fieldKey]) === false) {
            $this->config[$fieldKey] = Mage::getStoreConfig(
                $this->getConfigPath() . $fieldKey
            );
        }

        return $this->config[$fieldKey];
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return (bool)$this->getConfigValue('active');
    }

    /**
     * @return bool
     */
    public function isTestModeEnabled()
    {
        if ($this->getConfigValue('mode') === Safecharge_Safecharge_Model_Safecharge::MODE_LIVE) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getConfigValue('title');
    }

    /**
     * @return string
     */
    public function getMerchantId()
    {
        if ($this->isTestModeEnabled() === true) {
            return $this->getConfigValue('sandbox_merchant_id');
        }

        return $this->getConfigValue('merchant_id');
    }

    /**
     * @return string
     */
    public function getMerchantSiteId()
    {
        if ($this->isTestModeEnabled() === true) {
            return $this->getConfigValue('sandbox_merchant_site_id');
        }

        return $this->getConfigValue('merchant_site_id');
    }

    /**
     * @return string
     */
    public function getMerchantSecretKey()
    {
        if ($this->isTestModeEnabled() === true) {
            return $this->getConfigValue('sandbox_merchant_secret_key');
        }

        return $this->getConfigValue('merchant_secret_key');
    }

    /**
     * @return bool
     */
    public function getUseCcDetection()
    {
        return (bool)$this->getConfigValue('enable_cc_detection');
    }

    /**
     * @return bool
     */
    public function is3dSecureEnabled()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getPaymentAction()
    {
        return $this->getConfigValue('payment_action');
    }

    /**
     * @return string
     */
    public function getPaymentSolution()
    {
        return $this->getConfigValue('payment_solution');
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->getConfigValue('currency');
    }

    /**
     * @return bool
     */
    public function isDebugEnabled()
    {
        return (bool)$this->getConfigValue('debug');
    }

    /**
     * @return string
     */
    public function getCcTypes()
    {
        return $this->getConfigValue('cctypes');
    }

    /**
     * @return bool
     */
    public function getUseVault()
    {
        return (bool)$this->getConfigValue('use_vault');
    }

    /**
     * @return bool
     */
    public function getUseCcv()
    {
        return (bool)$this->getConfigValue('useccv');
    }

    /**
    * @return string
    */
    public function getSourcePlatformField()
    {
      $magentoVersion = Mage::getVersion();
      return $magentoVersion;
    }

    /**
     * Return AMP Methods.
     *
     * @return array
     */
    private function getApmMethods()
    {
        $request = Mage::getSingleton('safecharge_safecharge/api_request_factory')
          ->create()
        $request = $this->requestFactory->create(Safecharge_Safecharge_Model_Api_Request_Abstract::GET_MERCHANT_PAYMENT_METHODS_METHOD);

        try {
            $apmMethods = $request->process();
        } catch (PaymentException $e) {
            return [];
        }

        return $apmMethods->getPaymentMethods();
    }
}
