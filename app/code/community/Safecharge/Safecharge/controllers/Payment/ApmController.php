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

      $apmMethod = Mage::getSingleton('core/session')->getApmMethod();

      $configHelper = Mage::helper('safecharge_safecharge/config');

      if(!$configHelper->isActive()){
        if($configHelper->isDebugEnabled()){
            Mage::log('Apm Controller: Safecharge payments module is not active at the moment!', null, 'safecharge_safecharge_payment_redirect.log', true);
        }
        return $response->setBody(['error_message' => __('Safecharge payments module is not active at the moment!')]);
      }


      try {
        $request = Mage::getModel('safecharge_safecharge/api_request_factory')
        ->create(Safecharge_Safecharge_Model_Api_Request_Abstract::PAYMENT_APM_METHOD);
        $request->setPaymentMethod($apmMethod);

        $response = $request->process();
        $redirectUrl = $response->getRedirectUrl();
        $status = $response->getResponseStatus();
      } catch (PaymentException $e) {
        if($configHelper->isDebugEnabled()){
          Mage::log('Apm Controller - Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), null, 'safecharge_safecharge_payment_redirect.log', true);
        }
        Mage::getSingleton('checkout/session')->addError(
            __(
                'Order has been placed but unfortunately payment has been not '
                . 'authenticated properly.'
            )
        );
      }

      Mage::app()
        ->getResponse()
        ->setRedirect($redirectUrl)
        ->sendResponse();
    }
}
