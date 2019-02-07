<?php

/**
 * Safecharge Safecharge api payment user payment option request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Request_Payment_UserPaymentOption
    extends Safecharge_Safecharge_Model_Api_Request_Payment_Abstract
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_USER_PAYMENT_OPTION;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_USER_PAYMENT_OPTION;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getParams()
    {
        /** @var Mage_Sales_Model_Order_Payment $orderPayment */
        $orderPayment = $this->orderPayment;

        /** @var Mage_Sales_Model_Order $order */
        $order = $orderPayment->getOrder();

        $billing = $order->getBillingAddress();

        $tokenRequest = $this->getRequestFactory()
            ->create(Safecharge_Safecharge_Model_Api_Request_Abstract::METHOD_SESSION_TOKEN);
        $tokenResponse = $tokenRequest->process();

        $params = array(
            'sessionToken' => $tokenResponse->getToken(),
            'userTokenId' => $order->getCustomerId(),
            'ccTempToken' => $orderPayment->getAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::KEY_CC_TEMP_TOKEN
            ),
            'billingAddress' => array(
                'firstName' => $billing->getFirstname(),
                'lastName' => $billing->getLastname(),
                'address' => is_array($billing->getStreet())
                    ? implode(' ', (array)$billing->getStreet())
                    : '',
                'cell' => '',
                'phone' => $billing->getTelephone(),
                'zip' => $billing->getPostcode(),
                'city' => $billing->getCity(),
                'country' => $billing->getCountryId(),
                'state' => $billing->getRegionCode(),
                'email' => $billing->getEmail(),
            ),
        );

        $params = array_merge_recursive($params, parent::getParams());

        $this->requestEntity
            ->setParentRequestId($tokenRequest->getRequestId())
            ->setIncrementId($order->getIncrementId())
            ->save();

        return $params;
    }
}
