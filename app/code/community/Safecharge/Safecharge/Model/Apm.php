<?php

class Safecharge_Safecharge_Model_Apm extends Mage_Payment_Model_Method_Abstract {


  protected $_code;
  protected $_formBlockType = 'safecharge_safecharge/form_apm';
  protected $_infoBlockType = 'safecharge_safecharge/info_apm';
  /**
   * Safecharge_Safecharge_Helper_UrlBuilder constructor.
   */
  public function __construct() {
    if(!$this->useExternalSolution() && $this->paymentAction() === 'authorize_capture'){
      $this->_code  = 'safechargeapm';
    } else{
      $this->_code  = 'off';
    }
  }

  public function assignData($data)
   {
     $info = $this->getInfoInstance();
     $info->setAdditionalInformation('apmMethod', $data->getData('apmMethod'));
     return $this;
   }

   public function validate()
   {
     parent::validate();
     $info = $this->getInfoInstance();
     $apmMethod = $info->getAdditionalInformation('apmMethod');
     if (!$info->getAdditionalInformation('apmMethod'))
     {
       $errorMsg = $this->_getHelper()->__("please Select Payment method");
       Mage::throwException($errorMsg);
     } else {
         Mage::getSingleton('core/session')->setApmMethod($apmMethod);
     }

     return $this;
   }

   public function getCheckoutRedirectUrl()
   {
     return Mage::getUrl('safecharge/payment_apm/apm');
   }

  /**
   * @return bool
   */
  protected function useExternalSolution()
  {
      $paymentSolution = Mage::helper('safecharge_safecharge/config')->getPaymentSolution();
      $useExternal = $paymentSolution === Safecharge_Safecharge_Model_Safecharge::PAYMENT_SOLUTION_REDIRECT;

      return $useExternal;
  }

  protected function paymentAction(){
    return Mage::helper('safecharge_safecharge/config')->getPaymentAction();
  }
}
