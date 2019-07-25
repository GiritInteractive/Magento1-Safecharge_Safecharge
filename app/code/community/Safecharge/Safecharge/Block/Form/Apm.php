<?php

/**
 * Safecharge Safecharge payment block.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Block_Form_Apm extends Mage_Payment_Block_Form
{
    /**
     *
     */
     protected function _construct()
     {
       parent::_construct();
       $this->setTemplate('safecharge/safecharge/form/apm.phtml');
     }

     /**
      * @return array()
      */
      public function getPaymentMethods()
      {
        $request = Mage::getModel('safecharge_safecharge/api_request_factory')
         ->create(Safecharge_Safecharge_Model_Api_Request_Abstract::GET_MERCHANT_PAYMENT_METHODS_METHOD);

        $process = $request->process();
        $paymentMethods = $process->getPaymentMethods();

        return $paymentMethods;
      }
}
