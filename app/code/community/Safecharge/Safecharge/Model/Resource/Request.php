<?php

/**
 * Safecharge Safecharge request resource model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Resource_Request
    extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('safecharge_safecharge/request', 'log_id');
    }
}
