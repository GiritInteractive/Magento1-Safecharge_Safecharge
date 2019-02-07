<?php

class Safecharge_Safecharge_Model_Source_Mode
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Safecharge_Safecharge_Model_Safecharge::MODE_LIVE,
                'label' => Mage::helper('safecharge_safecharge')->__('Live'),
            ),
            array(
                'value' => Safecharge_Safecharge_Model_Safecharge::MODE_SANDBOX,
                'label' => Mage::helper('safecharge_safecharge')->__('Sandbox'),
            ),
        );
    }
}
