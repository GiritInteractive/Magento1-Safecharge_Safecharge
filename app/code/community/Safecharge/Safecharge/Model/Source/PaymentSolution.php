<?php

class Safecharge_Safecharge_Model_Source_PaymentSolution
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Safecharge_Safecharge_Model_Safecharge::PAYMENT_SOLUTION_INTEGRATED,
                'label' => Mage::helper('safecharge_safecharge')->__('SafeCharge API'),
            ),
            array(
                'value' => Safecharge_Safecharge_Model_Safecharge::PAYMENT_SOLUTION_REDIRECT,
                'label' => Mage::helper('safecharge_safecharge')->__('Hosted payment page'),
            ),
        );
    }
}
