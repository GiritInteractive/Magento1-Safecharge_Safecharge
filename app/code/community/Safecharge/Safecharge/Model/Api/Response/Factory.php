<?php

/**
 * Safecharge Safecharge abstract api response factory model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Response_Factory
{
    /**
     * @param string $method
     * @param Safecharge_Safecharge_Model_Request $requestEntity
     * @param Safecharge_Safecharge_Model_Api_Curl $curl
     *
     * @return false|Mage_Core_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function create($method, $requestEntity, $curl)
    {
        switch ($method) {
            case Safecharge_Safecharge_Model_Api_Response_Abstract::METHOD_SESSION_TOKEN:
                $model = $this->getInstance('token');
                break;
            case Safecharge_Safecharge_Model_Api_Response_Abstract::GET_MERCHANT_PAYMENT_METHODS_METHOD:
                $model = $this->getInstance('GetMerchantPaymentMethods');
                break;
            case Safecharge_Safecharge_Model_Api_Response_Abstract::PAYMENT_APM_METHOD:
                $model = $this->getInstance('PaymentMethod');
                break;
            default:
                throw new Mage_Core_Exception(
                    __('Unhandled response method.')
                );
        }

        if ($model === false) {
            throw new Mage_Core_Exception(
                __('Unhandled response method.')
            );
        }

        $model
            ->setRequestEntity($requestEntity)
            ->setCurl($curl);

        return $model;
    }

    /**
     * @param string $modelName
     *
     * @return false|Safecharge_Safecharge_Model_Api_Response_Abstract
     */
    protected function getInstance($modelName)
    {
        return Mage::getModel('safecharge_safecharge/api_response_' . $modelName);
    }
}
