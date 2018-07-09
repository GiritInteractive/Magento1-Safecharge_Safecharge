<?php

/**
 * Safecharge Safecharge api payment card tokenization request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Request_Payment_CardTokenization
    extends Safecharge_Safecharge_Model_Api_Request_Payment_Abstract
{
    /**
     * @return string
     */
    protected function getRequestMethod()
    {
        return Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_CARD_TOKENIZATION;
    }

    /**
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return Safecharge_Safecharge_Model_Api_Response_Payment_Abstract::METHOD_CARD_TOKENIZATION;
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
            'cardData' => array(
                'cardNumber' => $orderPayment->getCcNumber(),
                'cardHolderName' => $orderPayment->getCcOwner(),
                'expirationMonth' => $orderPayment->getCcExpMonth(),
                'expirationYear' => $orderPayment->getCcExpYear(),
                'CVV' => $orderPayment->getCcCid(),
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

    /**
     * @return array
     */
    protected function getChecksumKeys()
    {
        return array();
    }
}
