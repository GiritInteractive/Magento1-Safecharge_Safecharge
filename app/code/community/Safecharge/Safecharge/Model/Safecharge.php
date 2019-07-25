<?php

/**
 * Safecharge Safecharge payment model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Safecharge extends Mage_Payment_Model_Method_Cc
{
    const REQUEST_TYPE_AUTH_CAPTURE = 'AUTH_CAPTURE';
    const REQUEST_TYPE_AUTH_ONLY = 'AUTH_ONLY';
    const REQUEST_TYPE_CAPTURE_ONLY = 'CAPTURE_ONLY';
    const REQUEST_TYPE_CREDIT = 'CREDIT';
    const REQUEST_TYPE_VOID = 'VOID';

    const MODE_LIVE = 'LIVE';
    const MODE_SANDBOX = 'SANDBOX';

    const PAYMENT_SOLUTION_INTEGRATED = 'INTEGRATED';
    const PAYMENT_SOLUTION_REDIRECT = 'REDIRECT';

    const METHOD_CODE = 'safecharge';

    const KEY_CC_SAVE = 'cc_save';
    const KEY_CC_TOKEN = 'cc_token';
    const KEY_CC_TEMP_TOKEN = 'cc_temp_token';

    const TRANSACTION_REQUEST_ID = 'transaction_request_id';
    const TRANSACTION_ORDER_ID = 'safecharge_order_id';
    const TRANSACTION_AUTH_CODE_KEY = 'authorization_code';
    const TRANSACTION_ID = 'transaction_id';
    const TRANSACTION_CARD_NUMBER = 'card_number';
    const TRANSACTION_CARD_TYPE = 'card_type';
    const TRANSACTION_USER_PAYMENT_OPTION_ID = 'user_payment_option_id';
    const TRANSACTION_SESSION_TOKEN = 'session_token';
    const TRANSACTION_CARD_CVV = 'card_cvv';
    const TRANSACTION_PAYMENT_SOLUTION = 'payment_solution';
    const TRANSACTION_EXTERNAL_PAYMENT_METHOD = 'external_payment_method';

    const REQUEST_ENTITY_PERSISTENCE = 'safecharge_request_entity_persistence';

    const SC_AUTH = 'sc_auth';
    const SC_SETTLED = 'sc_settled';
    const SC_PARTIALLY_SETTLED = 'sc_partially_settled';
    const SC_VOIDED = 'sc_voided';

    protected $_code = self::METHOD_CODE;

    /**
     * Form block type
     */
    protected $_formBlockType = 'safecharge_safecharge/form_cc';

    /**
     * Info block type
     */
    protected $_infoBlockType = 'safecharge_safecharge/info_cc';

    /**
     * Availability options
     */
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc = true;
    protected $_canFetchTransactionInfo = true;

    /**
     * {@inheritdoc}
     *
     * @param mixed $data
     *
     * @return Safecharge_Safecharge_Model_Safecharge
     * @throws Mage_Core_Exception
     */
    public function assignData($data)
    {
        parent::assignData($data);

        $additionalData = $data->getData();

        $ccSave = !empty($additionalData[self::KEY_CC_SAVE])
            ? (bool)$additionalData[self::KEY_CC_SAVE]
            : false;

        $ccToken = !empty($additionalData[self::KEY_CC_TOKEN])
            ? $additionalData[self::KEY_CC_TOKEN]
            : null;

        if ($ccToken !== null) {
            $ccSave = false;
        }

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation(self::KEY_CC_SAVE, $ccSave);
        $info->setAdditionalInformation(self::KEY_CC_TOKEN, $ccToken);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param Mage_Payment_Model_Info $info
     *
     * @return Safecharge_Safecharge_Model_Safecharge
     */
    public function validate()
    {
        /** @var Safecharge_Safecharge_Helper_Config $config */
        $config = Mage::helper('safecharge_safecharge/config');

        $paymentSolution = $config->getPaymentSolution();
        if ($paymentSolution === self::PAYMENT_SOLUTION_REDIRECT) {
            return $this;
        }

        $info = $this->getInfoInstance();
        $tokenHash = $info->getAdditionalInformation(self::KEY_CC_TOKEN);

        if ($tokenHash === null) {
            parent::validate();

            return $this;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $currencyCode
     *
     * @return boolean
     */
    public function canUseForCurrency($currencyCode)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return Safecharge_Safecharge_Model_Safecharge
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     * @throws Mage_Payment_Exception
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        parent::authorize($payment, $amount);

        $this->processPayment($payment, $amount);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return Safecharge_Safecharge_Model_Safecharge
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     * @throws Mage_Payment_Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        parent::capture($payment, $amount);

        $this->processPayment($payment, $amount);

        return $this;
    }

    /**
     * Process payment request.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float                          $amount
     *
     * @return Safecharge_Safecharge_Model_Safecharge
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     * @throws Mage_Payment_Exception
     */
    protected function processPayment(
        Mage_Sales_Model_Order_Payment $payment,
        $amount
    ) {
        Mage::getSingleton('checkout/session')
            ->unsAscUrl()
            ->unsPaReq();

        /** @var Safecharge_Safecharge_Helper_Config $config */
        $config = Mage::helper('safecharge_safecharge/config');

        $paymentSolution = $config->getPaymentSolution();
        $payment->setAdditionalInformation(
            self::TRANSACTION_PAYMENT_SOLUTION,
            $paymentSolution
        );

        $authCode = $payment->getAdditionalInformation(self::TRANSACTION_AUTH_CODE_KEY);

        if ($authCode === null && $paymentSolution === self::PAYMENT_SOLUTION_REDIRECT) {
            $payment->setIsTransactionPending(true);

            return $this;
        }

        if ($authCode === null) {
            $secure3d = $config->is3dSecureEnabled();
            if ($secure3d === true) {
                $method = Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_DYNAMIC_3D;
            } else {
                $method = Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_CC;
            }
        } else {
            $method = Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_SETTLE;
        }

        $apiRequest = $this
            ->getPaymentRequestFactory()
            ->create($method, $payment, $amount);

        $response = $apiRequest->process();

        if ($method === Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_DYNAMIC_3D) {
            $this->finalize3dSecurePayment($response, $payment, $amount);
        }

        return $this;
    }

    /**
     * @param Safecharge_Safecharge_Model_Api_Response_Payment_Dynamic3D $response
     * @param Mage_Sales_Model_Order_Payment                        $payment
     * @param float                                                 $amount
     *
     * @return Safecharge_Safecharge_Model_Safecharge
     * @throws Mage_Core_Exception
     * @throws Mage_Payment_Exception
     * @throws Varien_Exception
     */
    protected function finalize3dSecurePayment(
        Safecharge_Safecharge_Model_Api_Response_Payment_Dynamic3D $response,
        Mage_Sales_Model_Order_Payment $payment,
        $amount
    ) {
        /** @var Safecharge_Safecharge_Helper_Config $config */
        $config = Mage::helper('safecharge_safecharge/config');

        $threeDFlow = (int)$response->getThreeDFlow();
        $ascUrl = $response->getAscUrl();

        if ($threeDFlow === 0 && $ascUrl === null) {
            /**
             * If the merchant’s configured mode of operation is sale,
             * then no further action is required.
             * If the merchant’s configured mode of operation is auth-settle,
             * then the merchant should call settleTransaction method afterwards.
             */
            if ($config->getPaymentAction() === self::ACTION_AUTHORIZE_CAPTURE) {
                $apiRequest = $this
                    ->getPaymentRequestFactory()
                    ->create(
                        Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_SETTLE,
                        $payment,
                        $amount
                    );
                $apiRequest->process();
            }

            return $this;
        }

        if ($threeDFlow === 1 && $ascUrl === null) {
            /**
             * The performed transaction will be 'sale’,
             * in order to complete the 'auth3D’ transaction
             * previously performed in dynamic3D method.
             */
            $apiRequest = $this
                ->getPaymentRequestFactory()
                ->create(
                    Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_PAYMENT_3D,
                    $payment,
                    $amount
                );
            $apiRequest
                ->setUserPaymentOptionId($response->getUserPaymentOptionId())
                ->setCardCvv($payment->getCcCid())
                ->process();

            return $this;
        }

        if ($threeDFlow === 1 && $ascUrl !== null) {
            /**
             * 1. Merchant should redirect to acsUrl.
             * 2. Merchant should call payment3D method afterwards.
             */
            Mage::getSingleton('checkout/session')
                ->setPaReq($response->getPaReq())
                ->setAscUrl($ascUrl);

            $payment->setIsTransactionPending(true);

            $payment->setAdditionalInformation(
                self::TRANSACTION_USER_PAYMENT_OPTION_ID,
                $response->getUserPaymentOptionId()
            );
            $payment->setAdditionalInformation(
                self::TRANSACTION_CARD_CVV,
                $payment->getCcCid()
            );

            return $this;
        }

        throw new Mage_Payment_Exception(
            __('Unexpected response during 3d secure payment handling.')
        );
    }

    /**
     * @return Safecharge_Safecharge_Model_Api_Request_Payment_Factory
     */
    protected function getPaymentRequestFactory()
    {
        return Mage::getSingleton('safecharge_safecharge/api_request_payment_factory');
    }

    /**
     * {@inheritdoc}
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return Safecharge_Safecharge_Model_Safecharge
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        parent::refund($payment, $amount);

        $apiRequest = $this
            ->getPaymentRequestFactory()
            ->create(
                Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_REFUND,
                $payment,
                $amount
            );

        $apiRequest->process();

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param Varien_Object $payment
     *
     * @return Safecharge_Safecharge_Model_Safecharge
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     */
    public function cancel(Varien_Object $payment)
    {
        parent::cancel($payment);

        $this->void($payment);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param Varien_Object $payment
     *
     * @return Safecharge_Safecharge_Model_Safecharge
     * @throws Mage_Core_Exception
     * @throws Varien_Exception
     */
    public function void(Varien_Object $payment)
    {
        parent::void($payment);

        $apiRequest = $this
            ->getPaymentRequestFactory()
            ->create(
                Safecharge_Safecharge_Model_Api_Request_Payment_Abstract::METHOD_VOID,
                $payment
            );

        $apiRequest->process();

        return $this;
    }

    /**
     * @return string
     * @throws Varien_Exception
     */
    public function getOrderPlaceRedirectUrl()
    {

        /** @var Safecharge_Safecharge_Helper_Config $config */
        $config = Mage::helper('safecharge_safecharge/config');

        /** @var string|null $ascUrl */
        $ascUrl = Mage::getSingleton('checkout/session')->getAscUrl();

        if ($config->is3dSecureEnabled() && $ascUrl) {
            return Mage::getUrl('safecharge/payment/authenticate');
        }

        return '';
    }


    /**
     * @return string
     * @throws Varien_Exception
     */
    public function getCheckoutRedirectUrl()
    {

      /** @var Safecharge_Safecharge_Helper_Config $config */
      $config = Mage::helper('safecharge_safecharge/config');

      $solution = $config->getPaymentSolution();
      if($solution == 'REDIRECT'){
        return Mage::getUrl('safecharge/payment/external');
      }
    }
}
