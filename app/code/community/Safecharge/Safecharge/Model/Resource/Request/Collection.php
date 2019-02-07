<?php

/**
 * Safecharge Safecharge request collection resource model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Resource_Request_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('safecharge_safecharge/request');
    }
}
