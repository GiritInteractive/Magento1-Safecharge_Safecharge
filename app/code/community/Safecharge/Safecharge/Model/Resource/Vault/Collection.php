<?php

/**
 * Safecharge Safecharge vault collection resource model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Resource_Vault_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init('safecharge_safecharge/vault');
    }

    /**
     * @return Safecharge_Safecharge_Model_Resource_Vault_Collection
     * @throws Varien_Exception
     */
    public function getVault()
    {
        $this->getSelect()
            ->where('payment_method_code = ?', Safecharge_Safecharge_Model_Safecharge::METHOD_CODE)
            ->where('is_active = ?', 1)
            ->where('is_visible = ?', 1)
            ->where('expires_at >= ?', Mage::getSingleton('core/date')->date());

        return $this;
    }

    /**
     * @param int $customerId
     *
     * @return Safecharge_Safecharge_Model_Resource_Vault_Collection
     * @throws Varien_Exception
     * @throws Mage_Core_Exception
     */
    public function getCustomerVault($customerId)
    {
        if (!$customerId || !is_int($customerId)) {
            throw new Mage_Core_Exception(
                __('Wrong customer id value provided.')
            );
        }

        $this->getVault();
        $this->getSelect()
            ->where('customer_id = ?', $customerId);

        return $this;
    }
}
