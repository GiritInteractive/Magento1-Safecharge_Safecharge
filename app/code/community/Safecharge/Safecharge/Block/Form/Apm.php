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
        foreach ($paymentMethods as $k => &$method) {
          if (Mage::helper('safecharge_safecharge/config')->getPaymentAction() === Safecharge_Safecharge_Model_Safecharge::ACTION_AUTHORIZE_CAPTURE && isset($method["paymentMethod"]) && $method["paymentMethod"] !== 'cc_card'){
            unset($this->paymentMethods[$k]);
            continue;
          }

          if (isset($method["paymentMethodDisplayName"]) && is_array($method["paymentMethodDisplayName"])) {
            foreach ($method["paymentMethodDisplayName"] as $kk => $dname) {
              if ($dname["language"] === $langCode) {
                $method["paymentMethodDisplayName"] = $dname;
                break;
              }
            }
            if (!isset($method["paymentMethodDisplayName"]["language"])) {
              unset($this->paymentMethods[$k]);
            }
          }
          if (isset($method["logoURL"]) && $method["logoURL"]) {
            $method["logoURL"] = preg_replace('/\.svg\.svg$/', '.svg', $method["logoURL"]);
          }
        }

        return $paymentMethods;
      }
}
