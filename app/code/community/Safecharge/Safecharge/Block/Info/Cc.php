<?php

/**
 * Safecharge Safecharge info block.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Block_Info_Cc extends Mage_Payment_Block_Info_Cc
{
    /**
     * @var array
     */
    protected $hiddenFields = array('cc_token');

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('safecharge/safecharge/info/default.phtml');
    }

    /**
     * Prepare credit card related payment info.
     *
     * @param Varien_Object|array $transport
     *
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $data = $transport->getData();

        if (!$this->getIsSecureMode()) {
            $additionalInformation = $this->getInfo()->getAdditionalInformation();
            foreach ($additionalInformation as $field => $value) {
                if (in_array($field, $this->hiddenFields, true)) {
                    unset($data[$field]);
                    continue;
                }

                $data[$this->getLabel($field)] = $value;
            }
        }

        return $transport->setData($data);
    }

    /**
     * @param string $field
     *
     * @return string
     */
    protected function getLabel($field)
    {
        $labels = array(
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_ID => __('Transaction Id'),
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_CARD_TYPE => __('Credit Card Type'),
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_CARD_NUMBER => __('Credit Card Number'),
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_AUTH_CODE_KEY => __('Authorization Code'),
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_ORDER_ID => __('Safecharge Order Id'),
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_REQUEST_ID => __('Internal Request Id'),
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_PAYMENT_SOLUTION => __('Payment Solution'),
            Safecharge_Safecharge_Model_Safecharge::TRANSACTION_EXTERNAL_PAYMENT_METHOD => __('External Payment Method'),
        );

        $label = $field;
        if (!empty($labels[$field])) {
            $label = $labels[$field];
        }

        return $label;
    }
}
