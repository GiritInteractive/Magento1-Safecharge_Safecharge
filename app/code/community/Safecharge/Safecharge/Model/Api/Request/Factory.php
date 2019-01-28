<?php

/**
 * Safecharge Safecharge abstract api request factory model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Request_Factory
{
    /**
     * @param string $method
     *
     * @return false|Mage_Core_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function create(
        $method
    ) {
        switch ($method) {
            case Safecharge_Safecharge_Model_Api_Request_Abstract::METHOD_SESSION_TOKEN:
                $model = $this->getInstance('token');
                break;
            case Safecharge_Safecharge_Model_Api_Request_Abstract::GET_MERCHANT_PAYMENT_METHODS_METHOD:
                $model = $this->getInstance('GetMerchantPaymentMethods');
                break;
            case Safecharge_Safecharge_Model_Api_Request_Abstract::PAYMENT_APM_METHOD:
                $model = $this->getInstance('PaymentMethod');
                break;
            default:
                throw new Mage_Core_Exception(
                    __('Unhandled request method.')
                );
        }

        if ($model === false) {
            throw new Mage_Core_Exception(
                __('Unhandled request method.')
            );
        }

        return $model;
    }

    /**
     * @param string $modelName
     *
     * @return false|Safecharge_Safecharge_Model_Api_Request_Payment_Abstract
     */
    protected function getInstance($modelName)
    {
        return Mage::getModel('safecharge_safecharge/api_request_' . $modelName);
    }
}
