<?php

/**
 * Safecharge Safecharge vault model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Vault extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('safecharge_safecharge/vault');
    }

    /**
     * @return Safecharge_Safecharge_Model_Vault
     * @throws Varien_Exception
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();

        if ($this->isObjectNew() === true) {
            $timestamp = Mage::getSingleton('core/date')->gmtTimestamp();
            $this->setCreatedAt($timestamp);
        }

        return $this;
    }

    /**
     * @param string $publicHash
     *
     * @return int
     */
    public function getIdByPublicHash($publicHash)
    {
        return $this->_getResource()->getIdByPublicHash($publicHash);
    }
}
