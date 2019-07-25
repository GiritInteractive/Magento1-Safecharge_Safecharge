<?php

class Safecharge_Safecharge_Model_Source_PaymentAction
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Safecharge_Safecharge_Model_Safecharge::ACTION_AUTHORIZE,
                'label' => Mage::helper('safecharge_safecharge')->__('Authorize Only'),
            ),
            array(
                'value' => Safecharge_Safecharge_Model_Safecharge::ACTION_AUTHORIZE_CAPTURE,
                'label' => Mage::helper('safecharge_safecharge')->__('Authorize & Capture'),
            ),
        );
    }
}
