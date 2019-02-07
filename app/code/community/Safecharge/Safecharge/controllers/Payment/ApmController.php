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
    public function apmAction(){

      $configHelper = Mage::helper('safecharge_safecharge/config');
      $response = $this->getResponse()
        ->clearHeaders()
        ->setHeader('HTTP/1.0', 200, true)
        ->setHeader('Content-Type', 'text/html');

      if(!$configHelper->isActive()){
        if($configHelper->isDebugEnabled()){
            Mage::log('Apm Controller: Safecharge payments module is not active at the moment!', null, 'safecharge_safecharge_payment_redirect.log', true);
        }
        return $response->setBody(['error_message' => __('Safecharge payments module is not active at the moment!')]);
      }

      try {
        $request = Mage::getModel('safecharge_safecharge/api_request_factory')
        ->create(Safecharge_Safecharge_Model_Api_Request_Abstract::PAYMENT_APM_METHOD);
        $request->setPaymentMethod('apmgw_expresscheckout');

        $response = $request->process();
        $redirectUrl = $response->getRedirectUrl();

        $status = $response->getResponseStatus();
      } catch (PaymentException $e) {
        if($configHelper->isDebugEnabled()){

        }
        return $result->setData([
          "error" => 1,
          "redirectUrl" => null,
          "message" => $e->getMessage()
        ]);
      }

      return $result->setData([
        "error" => 0,
        "redirectUrl" => $redirectUrl,
        "message" => $status
      ]);
    }


    /**
     * @return void
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     * @throws Exception
     */
    public function successAction()
    {

        /*$request = Mage::getModel('safecharge_safecharge/api_request_factory')
      ->create(Safecharge_Safecharge_Model_Api_Request_Abstract::GET_MERCHANT_PAYMENT_METHODS_METHOD);


      $process = $request->process();
      $paymentMethods = $process->getPaymentMethods();
      var_dump($paymentMethods);*/



      $request->setPaymentMethod('apmgw_expresscheckout');
      $response = $request->process();
      $redirectUrl = $response->getRedirectUrl();
      var_dump($redirectUrl);


    }

}
