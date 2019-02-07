<?php

/**
 * Safecharge Safecharge request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Request extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('safecharge_safecharge/request');
    }

    /**
     * @return Safecharge_Safecharge_Model_Request
     * @throws Mage_Core_Model_Store_Exception
     * @throws Varien_Exception
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();

        $timestamp = Mage::getSingleton('core/date')->gmtTimestamp();

        if ($this->isObjectNew() === true) {
            $this->generateRequestId();

            $this
                ->setStoreId(Mage::app()->getStore()->getStoreId())
                ->setCreatedAt($timestamp);

            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $this->setCustomerId(
                    Mage::getSingleton('customer/session')->getCustomerId()
                );
            }
        }

        $this->setUpdatedAt($timestamp);

        if (is_array($this->getRequest())) {
            $this->setRequest(Zend_Json::encode($this->getRequest()));
        }

        if (is_array($this->getResponse())) {
            $this->setResponse(Zend_Json::encode($this->getResponse()));
        }

        return $this;
    }

    /**
     * @return Safecharge_Safecharge_Model_Request
     * @throws Varien_Exception
     */
    protected function generateRequestId()
    {
        $timestamp = str_replace('.', '', microtime(true));
        $url = parse_url(Mage::getBaseUrl());
        if ($url === false || empty($url['host'])) {
            $url['host'] = 'dev';
        }

        $requestId = sprintf('%s-%s-%s', 'm1', $url['host'], $timestamp);
        $this->setRequestId($requestId);

        return $this;
    }
}
