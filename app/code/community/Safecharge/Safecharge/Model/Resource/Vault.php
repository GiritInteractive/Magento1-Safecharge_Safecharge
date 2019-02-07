<?php

/**
 * Safecharge Safecharge vault resource model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Resource_Vault
    extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('safecharge_safecharge/vault', 'vault_id');
    }

    /**
     * @param string $publicHash
     *
     * @return int|false
     * @throws Varien_Exception
     */
    public function getIdByPublicHash($publicHash)
    {
        $adapter = $this->_getReadAdapter();

        $tableName = Mage::getSingleton('core/resource')->getTableName(
            'safecharge_safecharge/vault'
        );
        $select = $adapter->select()
            ->from($tableName, 'vault_id')
            ->where('public_hash = :public_hash');

        $bind = array(':public_hash' => (string)$publicHash);

        return $adapter->fetchOne($select, $bind);
    }
}
