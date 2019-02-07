<?php

/**
 * Safecharge Safecharge card tokenization service.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Service_CardTokenization
{
    /**
     * @var Mage_Sales_Model_Order_Payment
     */
    protected $orderPayment;

    /**
     * @param Mage_Sales_Model_Order_Payment $orderPayment
     *
     * @return Safecharge_Safecharge_Model_Service_CardTokenization
     */
    public function setOrderPayment(Mage_Sales_Model_Order_Payment $orderPayment)
    {
        $this->orderPayment = $orderPayment;

        return $this;
    }

    /**
     * @return Safecharge_Safecharge_Model_Vault
     * @throws Mage_Core_Exception
     * @throws Mage_Payment_Exception
     * @throws Varien_Exception
     */
    public function processCardPaymentToken()
    {
        if ($this->orderPayment === null) {
            throw new Mage_Payment_Exception(
                __('Order payment object has been not set.')
            );
        }

        $ccTokenizeRequest = $this
            ->getPaymentRequestFactory()
            ->create(
                Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_CARD_TOKENIZATION,
                $this->orderPayment
            );
        $ccTokenizeResponse = $ccTokenizeRequest->process();

        $this->orderPayment->setAdditionalInformation(
            Safecharge_Safecharge_Model_Safecharge::KEY_CC_TEMP_TOKEN,
            $ccTokenizeResponse->getCcTempToken()
        );

        $userPaymentOptionRequest = $this
            ->getPaymentRequestFactory()
            ->create(
                Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_USER_PAYMENT_OPTION,
                $this->orderPayment
            );
        $userPaymentOptionResponse = $userPaymentOptionRequest->process();

        $paymentTokenDetails = array(
            'cc_type' => $this->orderPayment->getCcType(),
            'cc_last_4' => $this->orderPayment->getCcLast4(),
            'cc_exp_year' => $this->orderPayment->getCcExpYear(),
            'cc_exp_month' => $this->orderPayment->getCcExpMonth(),
        );

        $paymentTokenHash = md5(
            implode('', $paymentTokenDetails)
            . $this->orderPayment->getOrder()->getCustomerId()
            . Safecharge_Safecharge_Model_Safecharge::METHOD_CODE
        );

        /** @var Safecharge_Safecharge_Model_Vault $paymentToken */
        $paymentToken = Mage::getModel('safecharge_safecharge/vault');
        $paymentToken
            ->setCustomerId($this->orderPayment->getOrder()->getCustomerId())
            ->setPublicHash($paymentTokenHash)
            ->setPaymentMethodCode(Safecharge_Safecharge_Model_Safecharge::METHOD_CODE)
            ->setType(strtoupper($this->orderPayment->getCcType()))
            ->setGatewayToken($userPaymentOptionResponse->getCcToken())
            ->setTokenDetails(json_encode($paymentTokenDetails))
            ->setExpiresAt($this->getExpirationDate())
            ->setIsActive(1)
            ->setIsVisible(1)
            ->save();

        return $paymentToken;
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getExpirationDate()
    {
        $expDate = new \DateTime(
            $this->orderPayment->getCcExpYear()
            . '-'
            . $this->orderPayment->getCcExpMonth()
            . '-'
            . '01'
            . ' '
            . '00:00:00',
            new \DateTimeZone('UTC')
        );
        $expDate->add(new \DateInterval('P1M'));

        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Request_Payment_Factory
     */
    protected function getPaymentRequestFactory()
    {
        return Mage::getSingleton('safecharge_safecharge/api_request_payment_factory');
    }
}
