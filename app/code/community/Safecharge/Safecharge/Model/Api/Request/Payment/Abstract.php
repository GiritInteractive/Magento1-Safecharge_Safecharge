<?php

/**
 * Safecharge Safecharge abstract api payment request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
abstract class Safecharge_Safecharge_Model_Api_Request_Payment_Abstract
    extends Safecharge_Safecharge_Model_Api_Request_Abstract
{
    const METHOD_CC = 'paymentCC';
    const METHOD_SETTLE = 'settleTransaction';
    const METHOD_REFUND = 'refundTransaction';
    const METHOD_VOID = 'voidTransaction';
    const METHOD_CARD_TOKENIZATION = 'cardTokenization';
    const METHOD_USER_PAYMENT_OPTION = 'addUPOCreditCardByTempToken';
    const METHOD_DYNAMIC_3D = 'dynamic3D';
    const METHOD_PAYMENT_3D = 'payment3D';

    /**
     * @var Mage_Sales_Model_Order_Payment
     */
    protected $orderPayment;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @var Safecharge_Safecharge_Model_Service_CardTokenization
     */
    protected $cardTokenizationService;

    /**
     * Safecharge_Safecharge_Model_Api_Request_Payment_Abstract constructor.
     */
    public function __construct()
    {
        $this->cardTokenizationService = Mage::getSingleton(
            'safecharge_safecharge/service_cardTokenization'
        );

        parent::__construct();
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $orderPayment
     *
     * @return Safecharge_Safecharge_Model_Api_Request_Payment_Abstract
     */
    public function setOrderPayment(
        Mage_Sales_Model_Order_Payment $orderPayment
    ) {
        $this->orderPayment = $orderPayment;

        return $this;
    }

    /**
     * @param float $amount
     *
     * @return Safecharge_Safecharge_Model_Api_Request_Payment_Abstract
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return false|Safecharge_Safecharge_Model_Api_Response_Payment_Abstract
     * @throws Mage_Core_Exception
     */
    protected function getResponseHandler()
    {
        $responseHandler = $this
            ->getPaymentResponseFactory()
            ->create(
                $this->getResponseHandlerType(),
                $this->requestEntity,
                $this->curl,
                $this->orderPayment
            );

        return $responseHandler;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Response_Payment_Factory
     */
    protected function getPaymentResponseFactory()
    {
        return Mage::getSingleton('safecharge_safecharge/api_response_payment_factory');
    }

    /**
     * @return string
     * @throws Mage_Payment_Exception
     */
    protected function getActionType()
    {
        $paymentAction = $this->config->getPaymentAction();

        if ($paymentAction === Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE) {
            return 'Auth';
        }

        if ($paymentAction === Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE) {
            return 'Sale';
        }

        throw new Mage_Payment_Exception(__('Unsupported payment action type.'));
    }

    /**
     * @return array
     * @throws Mage_Payment_Exception
     * @throws Varien_Exception
     */
    protected function getPaymentData()
    {
        $paymentData = array();

        /** @var Mage_Sales_Model_Order_Payment $orderPayment */
        $orderPayment = $this->orderPayment;

        $ccToken = $orderPayment->getAdditionalInformation(
            Safecharge_Safecharge_Model_Safecharge::KEY_CC_TOKEN
        );
        if ($ccToken === null) {
            $paymentData['cardData'] = array(
                'cardNumber' => $orderPayment->getCcNumber(),
                'cardHolderName' => $orderPayment->getCcOwner(),
                'expirationMonth' => $orderPayment->getCcExpMonth(),
                'expirationYear' => $orderPayment->getCcExpYear(),
                'CVV' => $orderPayment->getCcCid(),
            );
        } else {
            /** @var Safecharge_Safecharge_Model_Vault $paymentToken */
            $paymentToken = Mage::getModel('safecharge_safecharge/vault');
            $paymentTokenId = $paymentToken->getIdByPublicHash($ccToken);

            if ($paymentTokenId === false) {
                throw new Mage_Payment_Exception(
                    __('Requested payment token does not exists.')
                );
            }

            $paymentToken->load($paymentTokenId);
            $paymentTokenDetails = $paymentToken->getTokenDetails();
            $paymentTokenDetails = json_decode($paymentTokenDetails, 1);

            $orderPayment
                ->setCcType($paymentTokenDetails['cc_type'])
                ->setCcLast4($paymentTokenDetails['cc_last_4'])
                ->setCcExpMonth($paymentTokenDetails['cc_exp_month'])
                ->setCcExpYear($paymentTokenDetails['cc_exp_year']);

            $paymentData['userPaymentOption'] = array(
                'CVV' => $orderPayment->getCcCid(),
                'userPaymentOptionId' => $paymentToken->getGatewayToken(),
            );
        }

        return $paymentData;
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Request_Payment_Abstract
     * @throws Mage_Core_Exception
     * @throws Mage_Payment_Exception
     * @throws Varien_Exception
     */
    protected function processCardTokenization()
    {
        /** @var Mage_Sales_Model_Order_Payment $orderPayment */
        $orderPayment = $this->orderPayment;

        $ccTokenize = $orderPayment->getAdditionalInformation(
            Safecharge_Safecharge_Model_Safecharge::KEY_CC_SAVE
        );
        if ($ccTokenize) {
            $orderPayment->unsAdditionalInformation(
                Safecharge_Safecharge_Model_Safecharge::KEY_CC_SAVE
            );
        }

        if (!$ccTokenize) {
            return $this;
        }

        $cardPaymentToken = $this->cardTokenizationService
            ->setOrderPayment($orderPayment)
            ->processCardPaymentToken();

        $this->orderPayment->setAdditionalInformation(
            Safecharge_Safecharge_Model_Safecharge::KEY_CC_TOKEN,
            $cardPaymentToken->getPublicHash()
        );

        return $this;
    }
}
