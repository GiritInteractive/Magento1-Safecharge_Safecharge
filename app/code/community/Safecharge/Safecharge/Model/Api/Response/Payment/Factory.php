<?php

/**
 * Safecharge Safecharge abstract api payment response factory model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Response_Payment_Factory
{
    /**
     * @param string                          $method
     * @param Safecharge_Safecharge_Model_Request  $requestEntity
     * @param Safecharge_Safecharge_Model_Api_Curl $curl
     * @param Mage_Sales_Model_Order_Payment  $orderPayment
     *
     * @return false|Mage_Core_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function create($method, $requestEntity, $curl, $orderPayment)
    {
        switch ($method) {
            case Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_CC:
                $model = $this->getInstance('cc');
                break;
            case Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_SETTLE:
                $model = $this->getInstance('settle');
                break;
            case Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_REFUND:
                $model = $this->getInstance('refund');
                break;
            case Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_VOID:
                $model = $this->getInstance('cancel');
                break;
            case Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_CARD_TOKENIZATION:
                $model = $this->getInstance('cardTokenization');
                break;
            case Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_USER_PAYMENT_OPTION:
                $model = $this->getInstance('userPaymentOption');
                break;
            case Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_DYNAMIC_3D:
                $model = $this->getInstance('dynamic3D');
                break;
            case Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_PAYMENT_3D:
                $model = $this->getInstance('payment3D');
                break;
            default:
                throw new Mage_Core_Exception(
                    __('Unhandled payment response method.')
                );
        }

        if ($model === false) {
            throw new Mage_Core_Exception(
                __('Unhandled payment response method.')
            );
        }

        $model
            ->setRequestEntity($requestEntity)
            ->setCurl($curl)
            ->setOrderPayment($orderPayment);

        return $model;
    }

    /**
     * @param string $modelName
     *
     * @return false|Safecharge_Safecharge_Model_Api_Response_Payment_Abstract
     */
    protected function getInstance($modelName)
    {
        return Mage::getModel('safecharge_safecharge/api_response_payment_' . $modelName);
    }
}
