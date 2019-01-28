<?php

/**
 * Safecharge Safecharge payment redirect controller.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Payment_ApmController
    extends Mage_Core_Controller_Front_Action
{

    /**
     * @return void
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     * @throws Exception
     */
    public function successAction()
    {
        $test = Mage::helper('safecharge_safecharge/config')->getApmMethods();
        var_dmup($test);


    }

}
